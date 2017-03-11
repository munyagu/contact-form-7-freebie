/**
 * Created by nagasawa on 2017/03/09.
 */
jQuery(function($){
    $input_element = $('.wpcf7').find('input,textarea').parent('.wpcf7-form-control-wrap');
    $input_element.parent().append($('<span class="cf7f_error_msg"></span>'));
});