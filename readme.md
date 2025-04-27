# 1. PLUGIN THAT GENERATE PDF

This plugin sets the scheme of templates and courses in the database.
The goal is to generate a PDF with dynamic data.

After that, I will create a queue to attach that PDF to emails.

TODO
 - add plugin istruction
 - meta data
 - download pdf
 - button download pdf


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

To protect the access of the customer's signature, I changed the folder where the file will be updated.
In function function DSCFW_save_image( $base64_img, $title ) I add:
<code>
$genpdf_folder = add_filter('folder','genpdf_get_singature_folder');
if( !empty($genpdf_folder)){
	$upload_dir  = $genpdf_folder;
}
</code>
