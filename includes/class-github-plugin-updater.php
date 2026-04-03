<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class GitHub_Plugin_Updater {

    private $owner;
    private $repo;
    private $plugin_file;
    private $plugin_slug;
    private $api_url;
    private $transient_key;
    private $cache_time;

    public function __construct( array $config ) {
        $this->owner        = $config['owner'];
        $this->repo         = $config['repo'];
        $this->plugin_file  = $config['plugin_file'];
        $this->plugin_slug  = $config['slug'] ?? $config['repo'];
        $this->cache_time   = $config['cache_time'] ?? 43200; // 12 ore
        $this->api_url      = 'https://api.github.com/repos/' . $this->owner . '/' . $this->repo . '/releases/latest';
        $this->transient_key = 'gh_updater_' . substr( hash( 'sha256', $this->repo ), 0, 20 );

        add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_update' ] );
        add_filter( 'plugins_api',                           [ $this, 'plugin_info' ], 10, 3 );
        add_filter( 'upgrader_package_options',              [ $this, 'fix_package_options' ] );
        add_action( 'upgrader_process_complete',             [ $this, 'clear_cache' ], 10, 2 );
    }

    // -------------------------------------------------------------------------
    // GitHub API
    // -------------------------------------------------------------------------

    private function get_latest_release() {
        $cached = get_transient( $this->transient_key );
        if ( false !== $cached ) {
            return $cached;
        }

        $response = wp_remote_get( $this->api_url, [
            'headers' => [
                'Accept'     => 'application/vnd.github+json',
                'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ),
            ],
            'timeout' => 15,
        ] );

        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $body['tag_name'] ) ) {
            return false;
        }

        $tag     = $body['tag_name'];
        $version = ltrim( $tag, 'v' );
        $zip_url = '';

        // Cerca lo ZIP tra gli asset della release
        if ( ! empty( $body['assets'] ) && is_array( $body['assets'] ) ) {
            foreach ( $body['assets'] as $asset ) {
                if (
                    ! empty( $asset['browser_download_url'] ) &&
                    str_ends_with( $asset['name'], '.zip' )
                ) {
                    $zip_url = $asset['browser_download_url'];
                    break;
                }
            }
        }

        // Fallback: URL costruito manualmente
        if ( empty( $zip_url ) ) {
            $zip_url = 'https://github.com/' . $this->owner . '/' . $this->repo
                    . '/releases/download/' . $tag . '/' . $this->repo . '-' . $tag . '.zip';
        }

        $release = [
            'version'   => $version,
            'tag'       => $tag,
            'zip_url'   => $zip_url,
            'changelog' => $body['body'] ?? '',
            'published' => $body['published_at'] ?? '',
        ];

        set_transient( $this->transient_key, $release, $this->cache_time );

        return $release;
    }

    private function get_installed_version() {
        if ( ! function_exists( 'get_plugin_data' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $file = WP_PLUGIN_DIR . '/' . $this->plugin_file;
        if ( ! file_exists( $file ) ) {
            return null;
        }
        $data = get_plugin_data( $file );
        return $data['Version'] ?? null;
    }

    // -------------------------------------------------------------------------
    // WordPress hooks
    // -------------------------------------------------------------------------

    public function check_update( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $release   = $this->get_latest_release();
        $installed = $this->get_installed_version();

        if ( ! $release || ! $installed ) {
            return $transient;
        }

        if ( version_compare( $release['version'], $installed, '>' ) ) {
            $transient->response[ $this->plugin_file ] = (object) [
                'slug'        => $this->plugin_slug,
                'plugin'      => $this->plugin_file,
                'new_version' => $release['version'],
                'url'         => 'https://github.com/' . $this->owner . '/' . $this->repo,
                'package'     => $release['zip_url'],
                'icons'       => [],
                'banners'     => [],
                'tested'      => get_bloginfo( 'version' ),
            ];
        }

        return $transient;
    }

    public function plugin_info( $result, $action, $args ) {
        if ( 'plugin_information' !== $action ) {
            return $result;
        }
        if ( ! isset( $args->slug ) || $args->slug !== $this->plugin_slug ) {
            return $result;
        }

        $release = $this->get_latest_release();
        if ( ! $release ) {
            return $result;
        }

        return (object) [
            'name'          => $this->repo,
            'slug'          => $this->plugin_slug,
            'version'       => $release['version'],
            'author'        => $this->owner,
            'homepage'      => 'https://github.com/' . $this->owner . '/' . $this->repo,
            'download_link' => $release['zip_url'],
            'last_updated'  => $release['published'],
            'sections'      => [
                'description' => 'Aggiornamento automatico da GitHub — <a href="https://github.com/'
                               . $this->owner . '/' . $this->repo . '" target="_blank">'
                               . $this->owner . '/' . $this->repo . '</a>',
                'changelog'   => ! empty( $release['changelog'] )
                                 ? nl2br( esc_html( $release['changelog'] ) )
                                 : '<p>Nessun changelog disponibile.</p>',
            ],
        ];
    }

    public function fix_package_options( $options ) {
        if (
            isset( $options['hook_extra']['plugin'] ) &&
            $this->plugin_file === $options['hook_extra']['plugin']
        ) {
            $options['destination']       = WP_PLUGIN_DIR . '/' . $this->plugin_slug;
            $options['clear_destination'] = true;
        }
        return $options;
    }

    public function clear_cache( $upgrader, $hook_extra ) {
        if (
            ! isset( $hook_extra['plugins'] ) ||
            ! in_array( $this->plugin_file, $hook_extra['plugins'], true )
        ) {
            return;
        }

        delete_transient( $this->transient_key );
    }
}