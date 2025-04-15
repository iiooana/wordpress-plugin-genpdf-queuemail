<? if (!is_admin()) {
    die("Access denied.");
} ?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?= __('Subscriptions received from the old website', 'genpdf-woocommerce') ?></h1>
    <div class="tablenav top">
        <div class="alignleft actions bulkactions">
        </div>
        <div class="alignleft actions">
        </div>
        <div class="tablenav-pages"><span class="displaying-num"><?= $subs->getCount() ?> <?= __('items', 'genpdf-woocommerce') ?></span>
            <span class="pagination-links"><span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>
                <span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>
                <span class="paging-input"><label for="current-page-selector" class="screen-reader-text">Current Page</label><input class="current-page" id="current-page-selector" type="text" name="paged" value="1" size="1" aria-describedby="table-paging"><span class="tablenav-paging-text"> of <span class="total-pages">2</span></span></span>
                <a class="next-page button" href="http://localhost:8081/wp-admin/edit.php?post_type=page&amp;paged=2"><span class="screen-reader-text">Next page</span><span aria-hidden="true">›</span></a>
                <span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span></span>
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
                <th><?= __('Created at', 'genpdf-woocommerce') ?></th>
            </tr>
        </thead>
        <tbody>
            <? $data  = $subs->getData(); ?>
            <? if(!empty($data) && count($data) > 0){ 
                foreach($data as $item ){
                    $detail = $subs->getShortDetail($item['id']);
                    ?>
                    <tr>
                        <td><?=$item['id']?></td>
                        <td> <?=$detail['nome']?> </td>
                        <td> <?=$detail['cognome']?> </td>
                        <td><a href="<?=$item['url']?>" target="_blank"><?=$item['url']?></a></td>
                        <td> <?= !empty($item['created']) ? date('H:i d/m/Y',strtotime($item['created'])) : ''?> </td>
                    </tr>
               <? }
             } ?>
        </tbody>
        <tfoot>
            <th>ID</th>
            <th><?= __('First name', 'genpdf-woocommerce') ?></th>
            <th><?= __('Last name', 'genpdf-woocommerce') ?></th>
            <th>URL</th>
            <th><?= __('Created at', 'genpdf-woocommerce') ?></th>
        </tfoot>
    </table>
</div>