$(document).ready(function() {
    if(parseInt($('input[name="agreement"]:checked').val()) === 0) {
        $('#requestform-agreementnumber').attr('disabled','disabled');        
    } else {
        $('#requestform-agreementnumber').removeAttr('disabled');
    }
    
    checkAgreement();
    
});
function countOfObject(obj) {
  var t = typeof(obj);
  var i=0;
  if (t!="object" || obj==null) return 0;
  for (x in obj) i++;
  return i;
}
function ajaxFormValidation() {    
    if(parseInt($('input[type="radio"][name="agreement"]:checked').val()) === 1) {
        $.post('/check-agreement', {
            agreement_number: parseInt($('#requestform-agreementnumber').val()),
            agr_pack_id:  parseInt($("input[type='radio'][name='product']:checked").val())
        }).done(function(data) {
            if(data){
                if(typeof data != 'undefined') {
                    var result = JSON.parse(data);
                    result = parseInt(result.check_agr_num_ds);
                    if(result == 1 || result == 2) {
                        $('#requestform-agreementnumber').parents('.js-agreement-value').removeClass('has-error');
                        $('.js-agreement-value-text').html('');
                        requestFormSubmit();
                        return true;
                    }                    
                    if(result == 3) {
                        $('#requestform-agreementnumber').parents('.js-agreement-value').addClass('has-error');
                        $('.js-agreement-value-text').html('Некорректный номер договора');
                        return false;
                    }                   
                }
            }
        });
    } else {
        requestFormSubmit();
        return true;
    }    
}
function requestFormSubmit() {
    var form = $('#request-form');
    if($('#house-status').attr("data-status") == 'disabled') {
        $('#request-form .well').css('border-color', '#3c763d');
        return false;
    } else if($("#selectDevices-internet").val() == null) {
         if(countOfObject(packagesData[$("input[type='radio'][name='product']:checked").val()]['products']) == 1 && 
             (typeof packagesData[$("input[type='radio'][name='product']:checked").val()]['products'][12] != 'undefined' || 
             typeof packagesData[$("input[type='radio'][name='product']:checked").val()]['products'][53] != 'undefined') || 
             $('.js-addendums').length > 0) {
            $( "#selectDevices-internet" ).css('border-color', '#ccc');
            $('#request-form .well').css('border-color', 'transparent');
            $('#request-form #packages-data').val(JSON.stringify(packagesData[$("input[type='radio'][name='product']:checked").val()]));
            $('#request-form #street_name').val($("#select2-chosen-1").html());
            form.submit();
            return true;
        } else {
            $("#selectDevices-internet").css('border-color', '#3c763d');
            return false;
        }
    } else {
        $( "#selectDevices-internet" ).css('border-color', '#ccc');
        $('#request-form .well').css('border-color', 'transparent');
        $('#request-form #packages-data').val(JSON.stringify(packagesData[$("input[type='radio'][name='product']:checked").val()]));
        $('#request-form #street_name').val($("#select2-chosen-1").html());
        form.submit();
        return true;
    }
}
$('input[name="product"]').click(function(){
    var currentData = packagesData[$("input[type='radio'][name='product']:checked").val()];
    if($('#street-select').val() != '' && parseInt($('#requestform-house option:selected').text()) != NaN) {
        if((countOfObject(currentData['products']) == 1 && typeof currentData['products'][12] == 'undefined') || countOfObject(currentData['products']) > 1) {
            
            var agreement_number = parseInt($('#requestform-agreementnumber').val());
            if(isNaN(agreement_number)) {
                agreement_number = 0;
            }
            
            $.post('/get-devices', {
                street: $('#street-select').val(),
                house_num: $('#requestform-house option:selected').text(),
                office: parseInt($('#requestform-flat').val()),
                material_to: Object.keys(currentData.products),
                flag_id: currentData.flag_id,
                agr_pack_id:  $("input[type='radio'][name='product']:checked").val(),
                agreement_number: agreement_number,
            }).done(function(data) {
                if(data){
                    if(typeof data != 'undefined') {

                        $('#request-form #products-data').val(data);

                        $('#selectDevices-internet, #selectDevices-domrutv').find('option').remove();                

                        var result = JSON.parse(data);                
                        if($.isArray(result.ds_materials_sa_list.rowset.materials)) {
                            $(result.ds_materials_sa_list.rowset.materials).each(function(index, value){
                                if(parseInt(value['@attributes'].product) == 5) {
                                    if($('#selectDevices-internet .js-has-own-router').length === 0 && countOfObject(currentData['products']) != 3) {
                                        $('#selectDevices-internet')
                                            .append($("<option class='js-has-own-router'></option>")
                                            .attr("value", -1)
                                            .text('У Клиента свой роутер'));
                                    }
                                    $('#selectDevices-internet')
                                        .append($("<option></option>")
                                        .attr("value", value['@attributes'].id)
                                        .text(value['@attributes'].name));
                                } else if(parseInt(value['@attributes'].product) == 53) {
                                    $('#selectDevices-domrutv')
                                        .append($("<option></option>")
                                        .attr("value", value['@attributes'].id)
                                        .text(value['@attributes'].name));
                                }
                            });
                        } else {                    
                            $('#selectDevices-internet, #selectDevices-domrutv').append('<option value="0">Нет</option>');
                            if(parseInt(result.ds_materials_sa_list.rowset.materials['@attributes'].product) == 5) {
                                $('#selectDevices-internet').find('option').remove();
                                $('#selectDevices-internet')
                                    .append($("<option></option>")
                                    .attr("value", result.ds_materials_sa_list.rowset.materials['@attributes'].id)
                                    .text(result.ds_materials_sa_list.rowset.materials['@attributes'].name));
                            } else {
                                $('#selectDevices-domrutv').find('option').remove();
                                $('#selectDevices-domrutv')
                                    .append($("<option></option>")
                                    .attr("value", result.ds_materials_sa_list.rowset.materials['@attributes'].id)
                                    .text(result.ds_materials_sa_list.rowset.materials['@attributes'].name));                       
                            }                    
                        }

                    }
                }
            });        
        } else {
            $('#selectDevices-internet').find('option').remove();
            $('#selectDevices-domrutv').find('option').remove();
        }
    }    
});
$('input[name="agreement"]').click(function() {
    if(parseInt($(this).val()) === 0) {
        $('#requestform-agreementnumber').attr('disabled','disabled');        
    } else {
        $('#requestform-agreementnumber').removeAttr('disabled');
    }
});
$('.js-submit-request').on('click', function() {   
    ajaxFormValidation();
    return false;    
});
$('#send-btn').click(function() {
    $(this).prop('disabled', true);
    $('#return-btn').prop('disabled', true);
    $('#confirm-form').submit();
});

$('#requestform-agreementnumber').keyup(function(){
    checkAgreement();
});

function checkAgreement() {
    var agreement_number = parseInt($('#requestform-agreementnumber').val()).toString();
    var value = agreement_number.length;    
    if(value == 12) {
        $.post('/get-packages', {
                agreement_number: agreement_number                
            }).done(function(data) {
                if(data){
                    if(typeof data != 'undefined') {
                        var result = JSON.parse(data);                        
                       
                        if(typeof result.addendums == 'undefined') {
                            $('.js-addendums').remove();
                        }
                       
                        for(var key in result) {
                            if(key != 'addendums') {
                                if(result[key].show == 0) {
                                    $("input[type='radio'][value='" + key + "']").attr('disabled', 'disabled');
                                    $("input[type='radio'][value='" + key + "']").parents('.product-item').addClass('disabled');
                                } else {
                                    $("input[type='radio'][value='" + key + "']").removeAttr('disabled');
                                    $("input[type='radio'][value='" + key + "']").parents('.product-item').removeClass('disabled');
                                }
                            } else {
                                var addendums = '';
                                for(var keyAdd in result[key]) {
                                    addendums += '<li>' + result[key][keyAdd].active_plan_name + '</li>';
                                }
                                $('.js-addendums').remove();
                                $('.js-devices').before('<p class="margin-t-15 text-info js-addendums"><strong>Приложения на договоре:</strong></p><ul class="js-addendums">' + addendums + '</ul>');
                            }
                        }
                        
                    }
                }
            });
    }
    if(isNaN(agreement_number)) {
        $('.js-addendums').remove();
    }
}