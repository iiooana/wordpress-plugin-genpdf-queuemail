# 1. PLUGIN THAT GENERATE PDF

This plugin generate dynamically PDF files from DB data.


## 1.1. TRANSLATE USGIN i18n
Requirements: wp cli

Steps:
<ol>
    <li>Add Text Domain and Domain Path as comment for your plugin.</li>
    <li>Create the template file <strong>.pot</strong>, go into folder or you plugin plugins/myplugin and then: <strong>wp i18n make-pot . </strong></li>
    <li>Copy the file from genpdf-woocommerce.pot to genpdf-woocommerce-{lang}.po</li>
    <li>We need to create the file <strong>.mo</strong>: msgfmt -o file-it_IT.mo file-
it_IT.po 
    </li>
    <li>Add load_plugin_textdomain() on plugins_loaded</li>
</ol>

## 1.2 Folder wp-content/signatures

To protect the access of the customer's signature, I create a folder wp-content/signatures where all signatures will be saved.


## 1.3 PDF
<ul>
<li>In the table _templates there is the list of templates. To change the PDF template, it is important to create new row in DB and update the id of "_genpdf_id_current_template_pdf" option. Never modify the current HTML of the DB table.</li>
<li>Some dynamic fields of PDF are from customer data, and some from the product; therefore, by ACF in the admin panel, I created new fields of the product. For the same reason, after the order creation, the plugin saves product metadata into the wc_orders_meta linked to the order_id. In this way, we can guarantee the correct data in case the product is updated in the future. </li>
<li>In the order list, there are buttons to download a PDF or send an email to the customer with attachments</li>
<li>After each order with successful payments, the customer will receive an email with all attachments.</li>
<li>To generate the PDF, I use Dompdf, unfortunately, I commit the vendor folder beacuse I don't have control over the website hosting. Usually the folder vendor is never committed, only the composer.json and composer.lock.</li>
</ul>