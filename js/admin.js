function service_price_input_show(id){
    jQuery('#service_' + id + '_price_input').show();
    jQuery('#service_' + id + '_price_input').focus();
}

function service_price_input_hide(id){
    jQuery('#service_' + id + '_price_input').hide();
}

function service_price_update(id){
    price_input = jQuery('#service_' + id + '_price_input');
    price_new = price_input.val();

    jQuery.ajax({
        type:'POST',
        url:ajaxurl,
        data:{
            action: 'service_price_update',
            post_id: id,
            price_new: price_new
        },
        beforeSend:function(xhr){
            price_input.attr('readonly','readonly').next().html('Сохраняю...');
        },
        success:function(results){
            price_input.removeAttr('readonly').next().html('<span style="color:#0FB10F">Сохранено</span>');
            service_price_input_hide(id);
            jQuery('#service_' + id + '_price').html(price_new);
        }
    });
}