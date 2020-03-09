<?php
/**
 * This file is the central location for all the learn links. The arrays are filterable by extensions
 * Links updated regularly. 
 * 
 * 
 *  HANDOVER CHECKS COMPLETE. BEEN THROUGH ALL PAGES IN PLUGIN NO DEBUG ERRORS. LOOKS GOOD TO GO.
 * 
 *  DO NOT CHANGE ANY FILTER NAMES AS THESE ARE USED IN FREELANCE WORK CUSTOM PLUGIN.
 * 
 * 
 */
global $zbs_learn_links_array, $zbs_learn_img_array, $zbs_learn_video_link_array, $zbs_learn_content_array;

#} Effectively a CMS of "learn" featured image, and more info! lol.
#} Shows how many of these we need to populate with content. Done images too (as this is probably something else worth tweaking)
$zbs_learn_links_array = array(

    #} Thoughts here on linkage. Currently I've linked them to our /feature/ page.
    #} If no feature page (e.g. tags) is in our list yet. It goes to a /kb/ page
    #} Eventually - have these all sending traffic to our feature pages, which in turn have documentation links

    #} Have put #NEEDS /FEATURE/ above the links that need a feature page 
    #} EDIT - have grouped all the #NEEDS /FEATURE/ ones near the bottom

    #} 30 July 2018 - all links now go somewhere sensible. Can adapt in future.

    'dashboard'         => 'https://zerobscrm.com/feature/dashboard/',

    'contactlist'       => 'https://zerobscrm.com/feature/contacts/', 
    'viewcontact'       => 'https://zerobscrm.com/feature/contacts/', 

    'contactnew'        => 'https://zerobscrm.com/feature/contacts/', 
    'contactedit'       => 'https://zerobscrm.com/feature/contacts/', 
    'newedit'           => 'https://zerobscrm.com/feature/contacts/', 

    'companytags'       => 'https://zerobscrm.com/feature/b2b-mode/', 

    'sendemail'         => 'https://zerobscrm.com/feature/system-emails/', 
    'viewcompany'       => 'https://zerobscrm.com/feature/b2b-mode/', 
    'newcompany'        => 'https://zerobscrm.com/feature/b2b-mode/',  

    'forms'             => 'https://zerobscrm.com/feature/forms/', 
    'editform'          => 'https://zerobscrm.com/feature/forms/', 
    'formnew'           => 'https://zerobscrm.com/feature/forms/', 

    'tasklist'          => 'https://zerobscrm.com/feature/tasks/', 
    'taskedit'          => 'https://zerobscrm.com/feature/tasks/',
    'tasknew'            => 'https://zerobscrm.com/feature/tasks/',
    
    'quotelist'         => 'https://zerobscrm.com/feature/quotes/',
    'quotenew'          => 'https://zerobscrm.com/feature/quotes/',
    'quoteedit'         => 'https://zerobscrm.com/feature/quotes/',
    
    'transactiontags'   => 'https://zerobscrm.com/feature/transactions/',
    'transnew'          => 'https://zerobscrm.com/feature/transactions/',
    'transedit'         => 'https://zerobscrm.com/feature/transactions/',

    'quotetemplate'     => 'https://zerobscrm.com/feature/quotes/',
    'quotetemplatenew'  => 'https://zerobscrm.com/feature/quotes/',
    'quotetemplateedit' => 'https://zerobscrm.com/feature/quotes/',
    
    'invoicelist'       => 'https://zerobscrm.com/feature/invoices/',
    'invoicenew'        => 'https://zerobscrm.com/feature/invoices/',
    'invoiceedit'       => 'https://zerobscrm.com/feature/invoices/',
    
    'team'              => 'https://zerobscrm.com/feature/team/',
    'teamadd'           => 'https://zerobscrm.com/feature/team/',
    'teamedit'          => 'https://zerobscrm.com/feature/team/',

    'powerup'           => 'https://zerobscrm.com/extension-bundles/',
    'companylist'       => 'https://zerobscrm.com/feature/companies/', 
    'companynew'        => 'https://zerobscrm.com/feature/companies/', 
    'companyedit'       => 'https://zerobscrm.com/feature/companies/', 

    'mail'              => 'https://zerobscrm.com/feature/system-emails/', 
    'maildelivery'      => 'https://zerobscrm.com/feature/mail-delivery/', 

    'segmentedit'       => 'https://zerobscrm.com/feature/segments/',
    'segmentlist'       => 'https://zerobscrm.com/feature/segments/',


    'contacttags'       => 'https://zerobscrm.com/feature/tags/', //'https://zerobscrm.com/kb/knowledge-base/how-do-we-make-our-own-tags-assign-customers-to-these-tags-and-filter-customers-by-these-tags/', 
    'notifications'     => 'https://zerobscrm.com/kb/knowledge-base/zero-bs-notifications/', 
 
    'exportcontact'     => 'https://zerobscrm.com/feature/contacts/', 

    'exporttools'       => 'https://zerobscrm.com/feature/tools/', 
    'bulktools'         => 'https://zerobscrm.com/feature/tools/', 
    'datatools'         => 'https://zerobscrm.com/feature/tools/', 


    'migratedb2contacts'=> 'https://zerobscrm.com/kb/knowledge-base/updating-contact-database-dbv2-migration/', 

    'connect'           => 'https://zerobscrm.com/kb/knowledge-base/how-do-i-update-an-extension/', 

    'systemstatus'      => 'https://zerobscrm.com/feature/tools/', 
    'export'            => 'https://zerobscrm.com/feature/tools/', 

    'bulktagger'        => 'https://zerobscrm.com/kb/article-categories/bulk-tagger/', 
    'salesdash'         => 'https://zerobscrm.com/kb/article-categories/sales-dashboard/', 
    'home'              => 'https://zerobscrm.com/', 

    'welcome'           => 'https://zerobscrm.com/',
    'sync'              => 'https://zerobscrm.com/extension-bundles/', 

    'settings'          => 'https://zerobscrm.com/kb/knowledge-base/settings-page/', 

    'emails'           => 'https://zerobscrm.com/feature/emails',

    #} your profile
    'profile'          => 'https://zerobscrm.com/feature/your-crm', 

);

#} Tried to use the same image where possible to save on plugin size when all zipped.
$zbs_learn_img_array = array(

    'dashboard'         => ZEROBSCRM_URL.'i/learn/learn-dashboard.png',

    'contactnew'        =>  ZEROBSCRM_URL.'i/learn/learn-import-contacts.png',

    'contactedit'       => ZEROBSCRM_URL.'i/learn/learn-edit-contact.png',
    
    'contacttags'       => ZEROBSCRM_URL.'i/learn/learn-contact-tags.png',
    'companytags'       => ZEROBSCRM_URL.'i/learn/learn-company-tags.png',

    'notifications'     => ZEROBSCRM_URL.'i/learn/learn-notifications.png',
    'sendemail'         => ZEROBSCRM_URL.'i/learn/learn-send-email.png',

    'contactlist'       => ZEROBSCRM_URL.'i/learn/learn-contact-list.png',
    'viewcontact'       => ZEROBSCRM_URL.'i/learn/learn-edit-contact.png',

    'companylist'       => ZEROBSCRM_URL.'i/learn/learn-company-list.png', 
    'companynew'        => ZEROBSCRM_URL.'i/learn/learn-company-list.png', 
    'companyedit'       => ZEROBSCRM_URL.'i/learn/learn-company-list.png', 

    'viewcompany'       => ZEROBSCRM_URL.'i/learn/learn-new-company.png',  

    'newedit'           => ZEROBSCRM_URL.'i/learn/learn-new-contact.png', 
    
    'newcompany'        => ZEROBSCRM_URL.'i/learn/learn-new-company.png',  

    'exportcontact'     => ZEROBSCRM_URL.'i/learn/learn-export-contacts.png', 
    'exporttools'       => ZEROBSCRM_URL.'i/learn/learn-export-tools.png',

    'forms'             => ZEROBSCRM_URL.'i/learn/learn-forms.png', 
    'formedit'          => ZEROBSCRM_URL.'i/learn/learn-forms.png', 
    'formnew'           => ZEROBSCRM_URL.'i/learn/learn-forms.png', 
  
    'tasklist'          => ZEROBSCRM_URL.'i/learn/learn-task-calendar.png', 
    'taskedit'          => ZEROBSCRM_URL.'i/learn/learn-task-calendar.png',
    'tasknew'           => ZEROBSCRM_URL.'i/learn/learn-task-calendar.png',

    'quotelist'         => ZEROBSCRM_URL.'i/learn/learn-quote-list.png', 
    'quotenew'          => ZEROBSCRM_URL.'i/learn/learn-new-quote.png', 
    'quoteedit'         => ZEROBSCRM_URL.'i/learn/learn-new-quote.png',
    
    
    'quotetemplate'     => ZEROBSCRM_URL.'i/learn/learn-quote-template.png', 
    'quotetemplatenew'  => ZEROBSCRM_URL.'i/learn/learn-quote-templates.png', 
    'quotetemplateedit' => ZEROBSCRM_URL.'i/learn/learn-quote-templates.png', 
    
    
    'transactiontags'   => ZEROBSCRM_URL.'i/learn/learn-trans-tags.png', 
    'translist'         => ZEROBSCRM_URL.'i/learn/learn-transactions-list.png', 

    'transnew'          => ZEROBSCRM_URL.'i/learn/learn-trans.png', 
    'transedit'         => ZEROBSCRM_URL.'i/learn/learn-trans.png', 
  

    'team'              => ZEROBSCRM_URL.'i/learn/learn-zbs-team.png',
    'teamadd'           => ZEROBSCRM_URL.'i/learn/learn-zbs-team.png', 
    'teamedit'          => ZEROBSCRM_URL.'i/learn/learn-zbs-team.png', 
   
    'invoicelist'       => ZEROBSCRM_URL.'i/learn/learn-invoice-list.png', 
    'invoicenew'        => ZEROBSCRM_URL.'i/learn/learn-new-invoice.png', 
    'invoiceedit'       => ZEROBSCRM_URL.'i/learn/learn-invoice-list.png', 
   
    'powerup'           => ZEROBSCRM_URL.'i/learn/learn-extensions-list.png',

    'bulktools'         => ZEROBSCRM_URL.'i/learn/learn-bulk-tools.png', 


    'datatools'         => ZEROBSCRM_URL.'i/learn/learn-data-tools.png', 


    #} Are these used?
    'bulktagger'        => ZEROBSCRM_URL.'i/learn/learn-extensions-list.png', //to do
    'salesdash'         => ZEROBSCRM_URL.'i/learn/learn-extensions-list.png', //to do
    'home'              => ZEROBSCRM_URL.'i/learn/learn-extensions-list.png', //to do

    #}system pages
    'connect'           => ZEROBSCRM_URL.'i/learn/learn-extensions-list.png', //to do - this will be depreciated soon when /update is out
  
    'systemstatus'      => ZEROBSCRM_URL.'i/learn/learn-system-settings.png', 

    'export'            => ZEROBSCRM_URL.'i/learn/learn-extensions-list.png', 
    'feedback'          => ZEROBSCRM_URL.'i/learn/learn-feedback.png', 

    'migratedb2contacts'=> ZEROBSCRM_URL.'i/learn/learn-extensions-list.png', 

    'segmentedit'       => ZEROBSCRM_URL.'i/learn/learn-segment-edit.png', 
    'segmentlist'       => ZEROBSCRM_URL.'i/learn/learn-segment-list.png', 
    
    'welcome'           => ZEROBSCRM_URL.'i/learn/learn-contact-list.png', 
    'sync'              => ZEROBSCRM_URL.'i/learn/learn-contact-list.png', 

    'maildelivery'      => ZEROBSCRM_URL.'i/learn/learn-mail-delivery.png', 
    'mail'              => ZEROBSCRM_URL.'i/learn/learn-mail.png', 

    'profile'          => ZEROBSCRM_URL.'i/learn/learn-your-profile.png', //to do

    'emails'            => ZEROBSCRM_URL.'i/learn/learn-emails.png', //to do

    'settings'            => ZEROBSCRM_URL.'i/learn/learn-settings-page.png', //to do

);


#} We should films these too :-) as part of marketing days - these are all currently set to FALSE in the CRM
$zbs_learn_video_link_array = array(

    'dashboard'         => false,

    'contactnew'        => false,
    'contactedit'       => false,
    'contacttags'       => false,
    'companytags'       => false,
    'notifications'     => false,
    'sendemail'         => false,
    'contactlist'       => false,
    'viewcontact'       => false,
    'viewcompany'       => false,
    'newedit'           => false,
    'newcompany'        => false,
    'exportcontact'     => false,
    'exporttools'       => false,
    'forms'             => false,
    'formedit'          => false,
    'formnew'           => false,
    'taskedit'          => false,
    'tasknew'           => false,
    'quotelist'         => false,
    'quotenew'          => false,
    'quoteedit'         => false,
    'transactiontags'   => false,
    'translist'         => false,
    'transnew'          => false,
    'transedit'         => false,
    'quotetemplate'     => false,
    'quotetemplatenew'  => false,
    'quotetemplateedit' => false,
    'team'              => false,
    'teamadd'           => false,
    'teamedit'          => false,
    'invoicelist'       => false,
    'invoicenew'        => false,
    'invoiceedit'       => false,
    'powerup'           => false,
    'bulktools'         => false,
    'datatools'         => false,
    'companylist'       => false,
    'companynew'        => false,
    'companyedit'       => false,
    'bulktagger'        => false,
    'salesdash'         => false,
    'home'              => false,
    'connect'           => false,
    'export'            => false,
    'systemstatus'      => false,
    'feedback'          => false,
    'tasklist'          => false,
    'migratedb2contacts'=> false,
    'segmentedit'       => false,
    'segmentlist'       => false,
    'welcome'           => false,
    'sync'              => false,
    'maildelivery'      => false,
    'mail'              => false,
    'emails'            => false,
    'profile'           => false,
    'settings'           => false
);


#} Doing to write all these now. Easy, single place to edit them going forwards. 

#} 30 July 2018 - all content learn paragraphs have text in related to the button now (a lot did not previously)
$zbs_learn_content_array = array(

    'dashboard'         => __('<p>This your CRM dashboard. It shows you at a glance some key data from your CRM activity.</p><p><b>Sales Funnel</b> shows how effective you are at converting leads to customers.</p><p><b>Revenue Chart</b> shows you the overview of your transactions for the past few months.</p>', 'zero-bs-crm'),
    'contactlist'       => __('<p>Here is your contact list. It is central to your CRM. You can manage your contacts here and apply quick filters.</p><p>Transactions Total is how much your contact has spent with you (for approved statuses). You can choose which transaction types should be included in your settings.</p><p>Total Value is the total value including other transaction statuses (pending, on-hold, etc) as well as the value of any unpaid invoices.</p>', 'zero-bs-crm'),
    'contactnew'        => __('<p>There are plenty of ways which you can add contacts to your CRM</p<p><br/><br/><strong>Adding them manually</strong> You can add contacts manually. This takes time.</p><p><strong>Import from CSV</strong> You can import via a CSV file.<p><strong>Import using our extensions</strong> such as PayPal Sync, Stripe Sync or WooSync which will help get your contacts into your CRM automatically.</p>'),
    'newedit'           => __('<p>There are plenty of ways which you can add contacts to your CRM</p<p><br/><br/><strong>Adding them manually</strong> You can add contacts manually. This takes time.</p><p><strong>Import from CSV</strong> You can import via a CSV file.<p><strong>Import using our extensions</strong> such as PayPal Sync, Stripe Sync or WooSync which will help get your contacts into your CRM automatically.</p>'),
    'contactedit'       => __('<p>Keep your contacts details up to date.</p><p><strong>Key details</strong> should be kept up to date here. Your contacts email, their address, plus any additional information you want to hold on them.</p><p>If the available fields below are not enough, you can add custom fields to your contacts record through the <a href="'.admin_url('admin.php?page=zerobscrm-plugin-settings&tab=customfields').'">custom field settings</p>'),
    'contacttags'       => __('<p>Tags are a powerful part of Zero BS CRM. You can tag your contacts and then filter or send emails based on those tags.</p><p>You can add as many tags as you like. Use them to keep track of important things with your contact. For example, contact has agreed to receive marketing material (or contact has opted out of marketing).</p>', 'zero-bs-crm'),
    'notifications'     => __('<p>When you are running your CRM you want to be kept up to date with everything.</p><p><p>Notifications are here to help keep you notified. Here is where you will see useful messages and updates from us.</p>','zero-bs-crm'),
    'sendemail'         => __('<p>Send your contact a single email from this page.</p><p><strong>Emails</strong> sent from here are logged against your contact in their Activity log</p><p><img style="max-width:90%" src="'.ZEROBSCRM_URL.'i/learn/learn-email-activity-log.png" /></p><p>Emails are sent using your chosen method of delivery (wp_mail, SMTP).</p>','zero-bs-crm'),
    'companytags'       => __('<p>Company Tags let you add tags to your Companies for easier filtering in the Company List</p><p>Tags help you organise your business a bit easier than just searching or filtering by status.</p>','zero-bs-crm'),
  
    'viewcontact'       => __('<p>View Contact gives you an easy way to see your contact information in one place.</p><p>You can customise what is shown on the view page by adding additional code to the system.</p>','zero-bs-crm'),
  
    'viewcompany'       => __('<p>Viewi Company gives you an overview of the key Company information. Including the ability to see which contacts work at the Company and click into viewing the contact information easily.</p>','zero-bs-crm'),
    'newcompany'        => __('<p>Add a New Compay to your CRM. When adding a Company you can also choose which contacts to assign to the Company.</p><p>Managing large clients, this gives you an easy way to zero in on contacts at a particular company.</p>','zero-bs-crm'),

    'exportcontact'     => __('<p>You can export your contact information here to do additional analysis outside of Zero BS CRM</p><p>Export and use in an Excel File, or export to import into other tools you use for your business needs</p>','zero-bs-crm'),
    'exporttools'       => __('<p>Here is the central area for exporting information from your CRM.</p><p>Export to keep backups offline. Export to do additional analysis and keep your CRM data in tact.</p>','zero-bs-crm'),


    'forms'             => __('<p>We offer built in Lead generation forms. Using these forms you can see which form is drivng the most growth in your list</p><p>If you do not want to use the built in Forms, you can use any of our Form connector extensions, such as Gravity Forms, or Contact Form 7.</p>','zero-bs-crm'),
    'formnew'           => __('<p>Add a New Form and choose your Form Type. Form Types are great to choose which type of layout you want on your site.</p><p>Each form tracks the number of views it has had compared to how many completions.</p><p>The more information you ask for on a form, the lower the completion rate. Only ask for what you need and keep your contact list growing fast</p>','zero-bs-crm'),

    'formedit'          => __('<p>Form Types are great to choose which type of layout you want on your site.</p><p>Each form tracks the number of views it has had compared to how many completions.</p><p>The more information you ask for on a form, the lower the completion rate. Only ask for what you need and keep your contact list growing fast</p>','zero-bs-crm'),

  
    'tasklist'          => __('<p>Tasks are our internal word for managing things to do related to contacts.</p><p>They are not intended to be a full appointment system operatable from the front end. They are useful to schedule short appointments and if using Client Portal Pro your clients can add them to their Calendar.</p>','zero-bs-crm'),
    'taskedit'          => __('<p>Tasks are our internal word for managing things to do related to contacts.</p><p>They are not intended to be a full appointment system operatable from the front end. They are useful to schedule short appointments and if using Client Portal Pro your clients can add them to their Calendar.</p>','zero-bs-crm'),
    'tasknew'           => __('<p>Tasks are our internal word for managing things to do related to contacts.</p><p>They are not intended to be a full appointment system operatable from the front end. They are useful to schedule short appointments and if using Client Portal Pro your clients can add them to their Calendar.</p>','zero-bs-crm'),
  
    'quotelist'         => __('<p>Here is your list of Quotes. You can see which quotes you have issued in the past.</p><p>You can also change the status of quotes in Bulk Actions (tick a quote row, then scroll to the bottom for Bulk Actions)</p>','zero-bs-crm'),
    'quotenew'          => __('<p>Add a new Quote here. When creating a Quote you fill in the key details such as customer name and quote value,you can then choose which template it should populate.</p><p>Templates automatically fill in the customer fields and save you time if you issue similar quotes regularly.</p>','zero-bs-crm'),
    'quoteedit'         => __('<p>When creating a Quote you fill in the key details such as customer name and quote value,you can then choose which template it should populate.</p><p>Templates automatically fill in the customer fields and save you time if you issue similar quotes regularly.</p>','zero-bs-crm'),

    'transactiontags'   => __('<p>Transaction tags can be used to filter your transaction list.</p><p>Some of our Sync tools like PayPal Sync or Woo Sync will automatically tag the transaction with the item name. This left you filter based on product and even feed into tag based filters in the Sales Dashboard extension</p>','zero-bs-crm'),

    'translist'         => __('<p>Here is your transactions list. This includes all transactions statuses such as completed, refunded, cancelled, failed. You can manage your transactions and see who has made them.</p><p>Transactions Total is how much your contact has spent with you (for approved statuses). You can choose which transaction types should be included in your settings.</p><p>Total Value is the total value including other transaction statuses (pending, on-hold, etc) as well as the value of any unpaid invoices.</p>','zero-bs-crm'),
   
    'transnew'          => __('<p>Adding a new Transaction is easy. You should assign it to a contact and then optionally to an invoice.</p><p>Assigned transactions are deducted from the balance of an invoice and feed into the total value for the contact</p><p>Be sure to define your transaction statuses to include in the total via the Transactions settings tab in settings.</p>','zero-bs-crm'),
    'transedit'         => __('<p>Editing a Transaction is easy. You should assign it to a contact and then optionally to an invoice.</p><p>Assigned transactions are deducted from the balance of an invoice and feed into the total value for the contact</p><p>Be sure to define your transaction statuses to include in the total via the Transactions settings tab in settings.</p>','zero-bs-crm'),
    
    
    'quotetemplate'     => __('<p>Quote Templates save you time. You can enter placeholders so that when you generate a new Quote using the template the customer fields are automatically populated.</p>','zero-bs-crm'),
    'quotetemplatenew'  => __('<p>A Quote Template is where you should populate all the business information when putting together a proposal or quote for your services</p><p>Templates save time meaning in new quotes you can just edit any price information and be up and running in seconds, vs typing out all the details again</p>','zero-bs-crm'),
    'quotetemplateedit' => __('<p>A Quote Template is where you should populate all the business information when putting together a proposal or quote for your services</p><p>Templates save time meaning in new quotes you can just edit any price information and be up and running in seconds, vs typing out all the details again</p>','zero-bs-crm'),
    
    'team'              => __('<p>Here is your CRM team. You can see what Role your team members have and see when they were last active.</p>','zero-bs-crm'),
    'teamadd'           => __('<p>As your business grows you will want to expand your team.</p><p>Add New Team Membersor search your WordPress users to add them to your team.</p><p>WordPress Administrator level by default has access to everything. You can manage your other user permissions here.</p>','zero-bs-crm'),
    'teamedit'          => __('<p>As your business grows you will want to expand your team.</p><p>Add New Team Membersor search your WordPress users to add them to your team.</p><p>WordPress Administrator level by default has access to everything. You can manage your other user permissions here.</p>','zero-bs-crm'),
    
    
    'invoicelist'       => __('<p>Here is your Invoice List. It shows you all your invoices in one place and you can manage the status, download PDF versions and keep everything in one place</p>','zero-bs-crm'),

    'invoicenew'        => __('<p>You\'re in business to get paid. Having invoices in your CRM is a great way to keep contacts and payments together.</p><p>Do you want to provide PDF invoices to your clients? Simple. Choose the PDF option and download your invoices as PDF.</p><p>The real power of invoicing comes when you allow your invoices to be accessed and paid straight from your client portal using Invoicing Pro.</p>','zero-bs-crm'),
    
    'invoiceedit'       => __('<p>You\'re in business to get paid. Having invoices in your CRM is a great way to keep contacts and payments together.</p><p>Do you want to provide PDF invoices to your clients? Simple. Choose the PDF option and download your invoices as PDF.</p><p>The real power of invoicing comes when you allow your invoices to be accessed and paid straight from your client portal using Invoicing Pro.</p>','zero-bs-crm'),

    'powerup'           => __('<p>Zero BS CRM is the ultimate Entrepreneur\'s CRM. The CORE of the CRM is free to use. We have developed extensions to the CRM which come in two types</p><p><b>Free Extensions</b> These are parts of the core that perhaps not everyone will use. Want any of the features? Activate or Deactivate them below.</p><p><b>Premium Extensions</b> We also have premium extensions available. Want all the extensions? Purchase our Entrepeneur\'s Bundle to get access to them all.</p>','zero-bs-crm'),
    'bulktools'         => __('<p>Bulk Tools let you run bulk deletion routines. Remove all contacts imported from a particular tool or service.</p><p>If you want to reset your whole CRM please contact support.</p>','zero-bs-crm'),
    'datatools'         => __('<p>Data Tools is the area where you can Bulk Delete contacts or Import from CSV.</p><p>You can also Export various types of CRM data, such as Contacts and Quotes and Invoices.</p></p>','zero-bs-crm'),
    'companylist'       => __('<p>Keep track of important Company level relationships in your CRM</p><p>Managing Companies is a way of seeing which contacts work at which Company. If you have three or four contacts who keep in touch with you, it is useful to know which Company they all share in common</p>','zero-bs-crm'),
    
    
    
    'companynew'        => __('<p>Add a New Compay to your CRM. When adding a Company you can also choose which contacts to assign to the Company.</p><p>Managing large clients, this gives you an easy way to zero in on contacts at a particular company.</p>','zero-bs-crm'),
    'companyedit'       => __('<p>Editing a Compay in your CRM. When editing a Company you can also choose which contacts to assign to the Company.</p><p>Managing large clients, this gives you an easy way to zero in on contacts at a particular company.</p>','zero-bs-crm'),





    'connect'           => '<p></p>',
    'export'            => '<p></p>',


    'systemstatus'      => __('<p>This page lets you see the various server and software settings which exist "behind the scenes" in your Zero BS CRM install.</p><p>You will not need to change anything here, but our support team might ask you to load this page to retrieve a status flag.</p>','zero-bs-crm'),
    
    'feedback'          => __('<p>Feedback helps us to improve this CRM for you and other users. We\'ve built almost all of what you can see in Zero BS CRM based on users feedback.</p><p> We read and intergrate all feedback into our future development.<b<>So send us your Feedback</b>.</p>','zero-bs-crm'),
  
    'migratedb2contacts'=> __('<p>Zero BS Forced updates are rare, but when they happen, they come with a pay off!</p><p>Running this Update routine will migrate your old contact data into the new database format, which has shown to be something in the order of 20x faster!</p>','zero-bs-crm'),

    'segmentedit'       => __('<p>Create a segment to partition a group of contacts into a manageable list.</p><p>Perfect for quick filters and links in seamlessly with Mail Campaigns and Automations. Segments are a great way to give you extra list power and save you having to manually group contacts based on multiple tags.</p>','zero-bs-crm'),
    'segmentlist'       => __('<p>Here is your segment list. This is where you will see any segments you create.</p><p>Segments are a powerful way to split out groups of contacts from your contact list and act on them (e.g. via Mail Campaigns or Automations).</p>','zero-bs-crm'),
 

    'maildelivery'      => __('<p>Mail Delivery options help you improve your CRM email deliverability. If you are running Mail Campaigns or our Mail Templates you may also wish to choose which email account sends the emails (or system emails).</p><p>You could have your new client account emails come from one email and your invoices come from another email</p>','zero-bs-crm'),
    'mail'              => __('<p>Your Mail settings control the emails that are sent out of your CRM.</p><p>You can choose how you want your email "From" name to look when single emails are sent and setup various mail delivery options (such as adding your STMP settings).</p>','zero-bs-crm'),


    'emails'            => __('<p>Emails are centric to your CRM communications. Send emails to your contacts and schedule them to send at certain times in the future (if conditions are met).</p><p>Check out our System Emails Pro extension for the fully featured email solution.</p>', 'zero-bs-crm'),

    #} where are these buttons lol
    'bulktagger'        => '<p></p>',
    'salesdash'         => '<p></p>',
    'home'              => '<p></p>',
    'welcome'           => '<p></p>',
    'sync'              => '<p></p>',

    #} profile
    'profile'              => __('<p>This is your profile page. It gives you useful information about your CRM and what you have been doing in it.</p><p>It is also the place where you can connect your online Calendar to the CRM and have it show up in the Task Scheduler Calendar.</p>','zero-bs-crm'),

    'settings'              => __('<p>This settings page lets you control all of the different areas of Zero BS CRM. As you install extensions you will also see their settings pages showing up on the left hand menu below.','zero-bs-crm'),

);



#} list of content filters, which could also be populated with rebrandr (or via custom plugin.)

/*

ORDER OF ONBOARDING.

#} Dashboard
zbs_learn_dashboard_content

#} Contacts
zbs_learn_contactlist_content
zbs_learn_viewcontact_content

zbs_learn_contactnew_content
zbs_learn_newedit_content
zbs_learn_contactedit_content
zbs_learn_contacttags_content

zbs_learn_exportcontact_content

#} Companies

zbs_learn_companylist_content
zbs_learn_companynew_content

zbs_learn_newcompany_content
zbs_learn_companyedit_content
zbs_learn_viewcompany_content
zbs_learn_companytags_content


#} Notifications
zbs_learn_notifications_content

#} Emails
zbs_learn_sendemail_content

#} Quotes
zbs_learn_quotelist_content
zbs_learn_quotenew_content
zbs_learn_quoteedit_content

zbs_learn_quotetemplate_content
zbs_learn_quotetemplatenew_content

#} Invoices
zbs_learn_invoicelist_content
zbs_learn_invoicenew_content
zbs_learn_invoiceedit_content

#} Transactions
zbs_learn_transactionlist_content
zbs_learn_transnew_content
zbs_learn_transedit_content


#} Forms
zbs_learn_forms_content
zbs_learn_editform_content
zbs_learn_formnew_content

#} Task Calendar
zbs_learn_taskedit_content
zbs_learn_tasknew_content

#} Tools
zbs_learn_exporttools_content
zbs_learn_bulktools_content
zbs_learn_datatools_content

#} Team
zbs_learn_team_content
zbs_learn_teamadd_content
zbs_learn_teamedit_content







*/



?>