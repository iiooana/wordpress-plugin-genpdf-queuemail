<?  wp_die("Access denied.");?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?= __('Subscriptions received from the old website', 'genpdf-woocommerce') ?></h1>
    <? if (!empty($_GET['page']) && $_GET['page'] == 'genpdf_data' && !empty($_GET['lead_id']) && is_numeric($_GET['lead_id'])) { ?>

        <?
        $lead = $subs->getLead($_GET['lead_id']);
        if (!empty($lead)) { ?>
            <table class="wp-list-table widefat fixed striped pages">
                <tbody>
                    <? $detail = $subs->getDetail($_GET['lead_id']);
                    if (!empty($detail)) {  ?>
                        <? foreach ($detail as $item) { ?>
                            <tr>
                                <td><?= !empty($item['name']) ? strtoupper($item['name']) : '' ?></td>
                                <td><strong><?= $item['value'] ?></strong></td>
                            </tr>
                        <? } ?>

                    <? } ?>
                    <tr>
                        <td> <?= strtoupper(__("date", 'genpdf-woocommerce')) ?> </td>
                        <td> <?= !empty($lead['created']) ? date('H:i:s d/m/Y', strtotime($lead['created'])) : '' ?> </td>
                    </tr>
                    <tr>
                        <td>IP</td>
                        <td><?= $lead['ip'] ?></td>
                    </tr>
                    <tr>
                        <td>Browser</td>
                        <td><?= $lead['browser'] ?></td>
                    </tr>
                    <tr>
                        <td>URL</td>
                        <td><a href="<?= $lead['url'] ?>" target="_blank"><?= $lead['url'] ?></a></td>
                    </tr>

                </tbody>
            </table>
        <? } ?>

    <? } else {
        if (!empty($_REQUEST['page']) && $_REQUEST['page'] == 'genpdf_data' && !empty($_REQUEST['search']) &&  !empty($_REQUEST['genpdf_search_value'])) {
            if (wp_verify_nonce($_REQUEST['genpdf_search_value'], 'genpdf_search') === false) {
                wp_die("Your token nonce is not valid.");
            }
            $subs->setSearch($_REQUEST['search']);
        }

        $max_page = $subs->getMaxNumberPages();
        if (!empty($_GET['n_page'])) {
            if (!is_numeric($_GET['n_page']) || intval($_GET['n_page']) > $max_page ) {
                wp_die("Page not valid");
            }
            $subs->setCurrentPage(intval($_GET['n_page']));
        }
        $current_page = $subs->getCurrentPage();
        $data  = $subs->getData();
       // genpdf_vardie($subs->getCount());
    ?>
        <div class="tablenav top">

            <div class="tablenav-pages">
                <form action="?" method="get">
                    <input type="hidden" name="page" value="genpdf_data">
                    <input type="hidden" name="n_page" value="<?= $current_page?>">
                    <? wp_nonce_field("genpdf_search", "genpdf_search_value") ?>
                    <input type="text" name="search" required value="<?=$subs->getSearch()?>">
                    <button class="button"><?= __("search", "genpdf-woocommerce") ?></button>
                </form>
                <div class="tablenav" style="display: flex; align-items:center; gap: 5px;">
                    <span class="displaying-num"><?= $subs->getCount() ?> <?= __('items', 'genpdf-woocommerce') ?></span>
                    <form action="?" method="get">
                        <input type="hidden" name="page" value="genpdf_data">
                        <input type="hidden" name="n_page" value="<?= $current_page-1 ?>">
                        <? wp_nonce_field("genpdf_search", "genpdf_search_value") ?>
                        <input type="hidden" name="search" required value="<?=$subs->getSearch()?>">
                        <button class="next-page button" <?=$current_page == 1 ? 'disabled':'' ?> title="<?= __('Go to page', 'genpdf-wocommerce') ?> <?= $current_page-1 ?>"><span aria-hidden="true">‹</span></button>
                    </form>
                    <span><?=$current_page?> <?=__('of','genpdf-woocommerce')?> <?=$max_page?></span>
                    <form action="?" method="get">
                        <input type="hidden" name="page" value="genpdf_data">
                        <input type="hidden" name="n_page" value="<?= $current_page + 1 ?>">
                        <? wp_nonce_field("genpdf_search", "genpdf_search_value") ?>
                        <input type="hidden" name="search" required value="<?=$subs->getSearch()?>">
                        <button class="next-page button" <?=$current_page==$max_page ? 'disabled': '' ?> title="<?= __('Go to page', 'genpdf-wocommerce') ?> <?= $current_page + 1 ?>"><span aria-hidden="true">›</span></button>
                    </form>
                </div>

            </div>
            <br class="clear">
        </div>
        <table class="wp-list-table widefat fixed striped pages">
            <thead>
                <tr>
                    <th>ID</th>
                    <th><?= __('First name', 'genpdf-woocommerce') ?></th>
                    <th><?= __('Last name', 'genpdf-woocommerce') ?></th>
                    <th>URL</th>
                    <th><?= __('Date', 'genpdf-woocommerce') ?></th>
                </tr>
            </thead>
            <tbody>
                <? if (!empty($data) && count($data) > 0) {
                    foreach ($data as $item) {
                        $detail = $subs->getShortDetail($item['id']);
                ?>
                        <tr>
                            <td> <?= $item['id'] ?> <a href="?page=genpdf_data&lead_id=<?= $item['id'] ?>"> <?= __('View', 'genpdf-woocommerce') ?> </a></td>
                            <td> <?= $detail['nome'] ?> </td>
                            <td> <?= $detail['cognome'] ?> </td>
                            <td><a href="<?= $item['url'] ?>" target="_blank"><?= $item['url'] ?></a></td>
                            <td> <?= !empty($item['created']) ? date('H:i:s d/m/Y', strtotime($item['created'])) : '' ?> </td>
                        </tr>
                <? }
                } ?>
            </tbody>
            <tfoot>
                <th>ID</th>
                <th><?= __('First name', 'genpdf-woocommerce') ?></th>
                <th><?= __('Last name', 'genpdf-woocommerce') ?></th>
                <th>URL</th>
                <th><?= __('Date', 'genpdf-woocommerce') ?></th>
            </tfoot>
        </table>
    <? }  ?>


</div>