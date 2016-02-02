({
    extendsFrom: 'RecordView',

    initialize: function (options) {
        app.view.invokeParent(this, {type: 'view', name: 'record', method: 'initialize', args:[options]});

        //add listener for custom button
        this.context.on('button:send_invoice_button:click', this.send_invoice, this);
    },

    send_invoice:function(){
        app.alert.show('send_invoice_loading',{level:'process',title:app.lang.get('LBL_LOADING')});
        var InvoiceID = this.model.get('id');
        $.ajax({
            url: '/rest/v10/TRANN_Trans/Send_Invoice',
            headers: {  'OAuth-Token': app.api.getOAuthToken() ,
                'X-Metadata-Hash': app.api.getMetadataHash(),
                'X-Userpref-Hash': app.api.getUserprefHash()
            },
            type: 'GET',
            data: {invoice: InvoiceID},
            success: function(data) {
                app.alert.show('send-ok', {
                    level: 'success',
                    messages: 'Invoice has been sent successfully.',
                    autoClose: true
                });
            }
        });
        app.alert.dismiss('send_invoice_loading');
    }
})