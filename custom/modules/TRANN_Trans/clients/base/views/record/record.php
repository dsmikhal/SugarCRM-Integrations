<?php
$viewdefs['TRANN_Trans'] = 
array (
  'base' => 
  array (
    'view' => 
    array (
      'record' => 
      array (
        'buttons' => 
        array (
          0 => 
          array (
            'type' => 'button',
            'name' => 'cancel_button',
            'label' => 'LBL_CANCEL_BUTTON_LABEL',
            'css_class' => 'btn-invisible btn-link',
            'showOn' => 'edit',
          ),
          1 => 
          array (
            'type' => 'rowaction',
            'event' => 'button:save_button:click',
            'name' => 'save_button',
            'label' => 'LBL_SAVE_BUTTON_LABEL',
            'css_class' => 'btn btn-primary',
            'showOn' => 'edit',
            'acl_action' => 'edit',
          ),
          2 => 
          array (
            'type' => 'actiondropdown',
            'name' => 'main_dropdown',
            'primary' => true,
            'showOn' => 'view',
            'buttons' => 
            array (
              0 => 
              array (
                'type' => 'rowaction',
                'event' => 'button:edit_button:click',
                'name' => 'edit_button',
                'label' => 'LBL_EDIT_BUTTON_LABEL',
                'acl_action' => 'edit',
              ),
              1 => 
              array (
                'type' => 'shareaction',
                'name' => 'share',
                'label' => 'LBL_RECORD_SHARE_BUTTON',
                'acl_action' => 'view',
              ),
              2 => 
              array (
                'type' => 'rowaction',
                'event' => 'button:send_invoice_button:click',
                'name' => 'send_invoice_button',
                'label' => 'LBL_INVOICE_BUTTON_LABEL',
                'acl_action' => 'view',
              ),
              3 => 
              array (
                'type' => 'pdfaction',
                'name' => 'download-pdf',
                'label' => 'LBL_PDF_VIEW',
                'action' => 'download',
                'acl_action' => 'view',
              ),
              4 => 
              array (
                'type' => 'pdfaction',
                'name' => 'email-pdf',
                'label' => 'LBL_PDF_EMAIL',
                'action' => 'email',
                'acl_action' => 'view',
              ),
              5 => 
              array (
                'type' => 'divider',
              ),
              6 => 
              array (
                'type' => 'rowaction',
                'event' => 'button:find_duplicates_button:click',
                'name' => 'find_duplicates_button',
                'label' => 'LBL_DUP_MERGE',
                'acl_action' => 'edit',
              ),
              7 => 
              array (
                'type' => 'rowaction',
                'event' => 'button:duplicate_button:click',
                'name' => 'duplicate_button',
                'label' => 'LBL_DUPLICATE_BUTTON_LABEL',
                'acl_module' => 'TRANN_Trans',
                'acl_action' => 'create',
              ),
              8 => 
              array (
                'type' => 'rowaction',
                'event' => 'button:audit_button:click',
                'name' => 'audit_button',
                'label' => 'LNK_VIEW_CHANGE_LOG',
                'acl_action' => 'view',
              ),
              9 => 
              array (
                'type' => 'divider',
              ),
              10 => 
              array (
                'type' => 'rowaction',
                'event' => 'button:delete_button:click',
                'name' => 'delete_button',
                'label' => 'LBL_DELETE_BUTTON_LABEL',
                'acl_action' => 'delete',
              ),
            ),
          ),
          3 => 
          array (
            'name' => 'sidebar_toggle',
            'type' => 'sidebartoggle',
          ),
        ),
        'panels' => 
        array (
          0 => 
          array (
            'name' => 'panel_header',
            'label' => 'LBL_RECORD_HEADER',
            'header' => true,
            'fields' => 
            array (
              0 => 
              array (
                'name' => 'picture',
                'type' => 'avatar',
                'width' => 42,
                'height' => 42,
                'dismiss_label' => true,
                'readonly' => true,
              ),
              1 => 'name',
              2 => 
              array (
                'name' => 'favorite',
                'label' => 'LBL_FAVORITE',
                'type' => 'favorite',
                'readonly' => true,
                'dismiss_label' => true,
              ),
              3 => 
              array (
                'name' => 'follow',
                'label' => 'LBL_FOLLOW',
                'type' => 'follow',
                'readonly' => true,
                'dismiss_label' => true,
              ),
            ),
          ),
          1 => 
          array (
            'name' => 'panel_body',
            'label' => 'LBL_RECORD_BODY',
            'columns' => 2,
            'labelsOnTop' => false,
            'placeholders' => true,
            'newTab' => false,
            'panelDefault' => 'expanded',
            'fields' => 
            array (
              0 => 
              array (
                'name' => 'document_date',
                'label' => 'LBL_DOCUMENT_DATE',
              ),
              1 => 
              array (
                'name' => 'contacts_trann_trans_1_name',
                'label' => 'LBL_CONTACTS_TRANN_TRANS_1_FROM_CONTACTS_TITLE',
              ),
              2 => 
              array (
                'name' => 'trans_type',
                'studio' => 'visible',
                'label' => 'LBL_TRANS_TYPE',
              ),
              3 => 
              array (
                'name' => 'due_date',
                'label' => 'LBL_DUE_DATE',
              ),
              4 => 
              array (
                'name' => 'invoice_class',
                'studio' => 'visible',
                'label' => 'LBL_INVOICE_CLASS',
              ),
              5 => 
              array (
                'name' => 'primary_client',
                'studio' => 'visible',
                'label' => 'LBL_PRIMARY_CLIENT',
              ),
              6 => 
              array (
                'name' => 'event',
                'studio' => 'visible',
                'label' => 'LBL_EVENT',
              ),
              7 => 
              array (
                'name' => 'transactions',
                'studio' => 'visible',
                'label' => 'LBL_TRANSACTIONS',
              ),
              8 => 
              array (
                'name' => 'create_refund',
                'label' => 'LBL_CREATE_REFUND',
              ),
              9 => 
              array (
                'name' => 'cancel_reason_c',
                'studio' => 'visible',
                'label' => 'LBL_CANCEL_REASON',
              ),
              10 => 
              array (
                'name' => 'do_not_push',
                'label' => 'LBL_DO_NOT_PUSH',
              ),
              11 => 
              array (
                'name' => 'company',
                'studio' => 'visible',
                'label' => 'LBL_COMPANY',
              ),
              12 => 
              array (
                'name' => 'megapack_c',
                'label' => 'LBL_MEGAPACK',
              ),
              13 => 
              array (
                'name' => 'email_address',
                'label' => 'LBL_EMAIL_ADDRESS',
              ),
              14 => 
              array (
                'name' => 'mega_child_ticket_c',
                'studio' => 'visible',
                'label' => 'LBL_MEGA_CHILD_TICKET',
              ),
              15 => 
              array (
                'name' => 'mega_child_event_c',
                'studio' => 'visible',
                'label' => 'LBL_MEGA_CHILD_EVENT',
              ),
              16 => 
              array (
                'name' => 'external_memo',
                'studio' => 'visible',
                'label' => 'LBL_EXTERNAL_MEMO',
              ),
              17 => 
              array (
                'name' => 'referral_source',
                'studio' => 'visible',
                'label' => 'LBL_REFERRAL_SOURCE',
              ),
              18 => 
              array (
                'name' => 'sales_rep',
                'studio' => 'visible',
                'label' => 'LBL_SALES_REP',
              ),
              19 => 
              array (
                'name' => 'affiliate',
                'studio' => 'visible',
                'label' => 'LBL_AFFILIATE',
              ),
            ),
          ),
          2 => 
          array (
            'name' => 'panel_hidden',
            'label' => 'LBL_SHOW_MORE',
            'hide' => true,
            'columns' => 2,
            'labelsOnTop' => true,
            'placeholders' => true,
            'newTab' => false,
            'panelDefault' => 'collapsed',
            'fields' => 
            array (
              0 => 
              array (
                'name' => 'accounting_submitted',
                'label' => 'LBL_ACCOUNTING_SUBMITTED',
              ),
              1 => 
              array (
                'name' => 'accounting_submitted_date',
                'label' => 'LBL_ACCOUNTING_SUBMITTED_DATE',
              ),
              2 => 'assigned_user_name',
              3 => 
              array (
                'name' => 'company_name',
                'label' => 'LBL_COMPANY_NAME',
              ),
              4 => 
              array (
                'name' => 'date_modified_by',
                'readonly' => true,
                'inline' => true,
                'type' => 'fieldset',
                'label' => 'LBL_DATE_MODIFIED',
                'fields' => 
                array (
                  0 => 
                  array (
                    'name' => 'date_modified',
                  ),
                  1 => 
                  array (
                    'type' => 'label',
                    'default_value' => 'LBL_BY',
                  ),
                  2 => 
                  array (
                    'name' => 'modified_by_name',
                  ),
                ),
              ),
              5 => 
              array (
                'name' => 'date_entered_by',
                'readonly' => true,
                'inline' => true,
                'type' => 'fieldset',
                'label' => 'LBL_DATE_ENTERED',
                'fields' => 
                array (
                  0 => 
                  array (
                    'name' => 'date_entered',
                  ),
                  1 => 
                  array (
                    'type' => 'label',
                    'default_value' => 'LBL_BY',
                  ),
                  2 => 
                  array (
                    'name' => 'created_by_name',
                  ),
                ),
              ),
            ),
          ),
          3 => 
          array (
            'newTab' => false,
            'panelDefault' => 'collapsed',
            'name' => 'LBL_RECORDVIEW_PANEL3',
            'label' => 'LBL_RECORDVIEW_PANEL3',
            'columns' => '2',
            'labelsOnTop' => 1,
            'placeholders' => 1,
            'fields' => 
            array (
              0 => 
              array (
                'name' => 'create_payment',
                'label' => 'LBL_CREATE_PAYMENT',
              ),
              1 => 
              array (
                'name' => 'create_pplan_c',
                'label' => 'LBL_CREATE_PPLAN',
              ),
              2 => 
              array (
                'name' => 'payment_method',
                'studio' => 'visible',
                'label' => 'LBL_PAYMENT_METHOD',
              ),
              3 => 
              array (
                'name' => 'pn_reference',
                'label' => 'LBL_PN_REFERENCE',
              ),
              4 => 
              array (
                'name' => 'amount_to_pay',
                'label' => 'LBL_AMOUNT_TO_PAY',
              ),
              5 => 
              array (
                'name' => 'master_product',
                'studio' => 'visible',
                'label' => 'LBL_MASTER_PRODUCT',
              ),
            ),
          ),
          4 => 
          array (
            'newTab' => false,
            'panelDefault' => 'expanded',
            'name' => 'LBL_RECORDVIEW_PANEL2',
            'label' => 'LBL_RECORDVIEW_PANEL2',
            'columns' => '2',
            'labelsOnTop' => 1,
            'placeholders' => 1,
            'fields' => 
            array (
              0 => 
              array (
                'name' => 'product_1',
                'studio' => 'visible',
                'label' => 'LBL_PRODUCT_1',
              ),
              1 => 
              array (
                'name' => 'amount_1',
                'label' => 'LBL_AMOUNT_1',
              ),
              2 => 
              array (
                'name' => 'product_2',
                'studio' => 'visible',
                'label' => 'LBL_PRODUCT_2',
              ),
              3 => 
              array (
                'name' => 'amount_2',
                'label' => 'LBL_AMOUNT_2',
              ),
              4 => 
              array (
                'name' => 'product_3',
                'studio' => 'visible',
                'label' => 'LBL_PRODUCT_3',
              ),
              5 => 
              array (
                'name' => 'amount_3',
                'label' => 'LBL_AMOUNT_3',
              ),
              6 => 
              array (
                'name' => 'product_4',
                'studio' => 'visible',
                'label' => 'LBL_PRODUCT_4',
              ),
              7 => 
              array (
                'name' => 'amount_4',
                'label' => 'LBL_AMOUNT_4',
              ),
            ),
          ),
          5 => 
          array (
            'newTab' => false,
            'panelDefault' => 'expanded',
            'name' => 'LBL_RECORDVIEW_PANEL1',
            'label' => 'LBL_RECORDVIEW_PANEL1',
            'columns' => '2',
            'labelsOnTop' => 1,
            'placeholders' => 1,
            'fields' => 
            array (
              0 => 
              array (
                'name' => 'amount_total',
                'label' => 'LBL_AMOUNT_TOTAL',
              ),
              1 => 
              array (
                'name' => 'amount_paid',
                'label' => 'LBL_AMOUNT_PAID',
              ),
              2 => 
              array (
                'name' => 'amount_tax',
                'label' => 'LBL_AMOUNT_TAX ',
              ),
              3 => 
              array (
                'name' => 'amount_net',
                'label' => 'LBL_AMOUNT_NET',
              ),
              4 => 
              array (
                'name' => 'amount_remaining',
                'label' => 'LBL_AMOUNT_REMAINING ',
                'span' => 12,
              ),
            ),
          ),
        ),
        'templateMeta' => 
        array (
          'maxColumns' => '2',
          'useTabs' => false,
        ),
      ),
    ),
  ),
);
