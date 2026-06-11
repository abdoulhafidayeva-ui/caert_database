var $loading = $('<div class="loading"><span class="fa fa-spinner fa-spin"></span></div>');
var UrlVerif = $('#pathUrl').val();
//alert(UrlVerif)
var spinner = $('#loader');
function uuid() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
        var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}
function defaulttextRemove() {
    $('.default').each(function () {
        var defaultVal = $(this).attr('title');
        if ($(this).val() == defaultVal) {
            $(this).val('');
        }
    });
}
function selectUser(users, email) {
    var optionsSelect = "";
    $.each(users, function (index, value) {
        if (email == value.email)
            optionsSelect += '<option value="' + value.id + '" selected="selected">' + value.nom + '</option>';
        else
            optionsSelect += '<option value="' + value.id + '">' + value.nom + '</option>';
    });
    return optionsSelect;
}
$(".default").each(function () {
    var defaultVal = $(this).attr('title');
    $(this).focus(function () {
        if ($(this).val() == defaultVal) {
            $(this).removeClass('active').val('');
        }
    });
    $(this).blur(function () {
        if ($(this).val() == '') {
            $(this).addClass('active').val(defaultVal);
        }
    })
        .blur().addClass('active');
});
function verifEmail() {
    // Run the email validation using the regex for those input items also having class "email"
    var $parentTag = $('#user_registration_form_email').parent();
    var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
    var email = $('#user_registration_form_email').val();
    $('.email-error').html('');
    $parentTag.removeClass('error email-error');
    if (!emailReg.test(email)) {
        $parentTag.addClass('error').append('<span class="error email-error">Entrer un adresse email valide.</span>');
    }
    if (emailReg.test(email)) {
        $.ajax({
            type: "POST",
            url: UrlVerif,
            data: {
                email: email
            }
        }).done(function (data) {
        }).fail(function (data) {
            $parentTag.addClass('error').append('<span class="error email-error">Addresse email déja utilisé.</span>');
        });
    }
};
$(function () {
    $('form#newUserForm').on('submit', function (event) {
        event.preventDefault(); // Prevent the form from submitting via the browser
        // spinner.show();
        var form = $(this);
        // In preparation for validating the form - Remove any active default text and previous errors
        defaulttextRemove();
        $('div', form).removeClass('error');
        $('span.error').remove();
        // Start validation by selecting all inputs with the class "required"
        $('input.required', form).each(function () {
            var inputVal = $(this).val();
            var $parentTag = $(this).parent();
            if (inputVal == '') {
                $parentTag.addClass('error').append('<span class="error">Obligatoire</span>');
            }
        });
        verifEmail();
        //alert($('span.error').length);
        // All validation complete - check whether any errors exist - if not submit form
        if ($('span.error').length == "0") {
            spinner.show();
            $('#bntLoadingAjax').attr("disabled", true);
            $('#bntLoadingIAjax').addClass("fa fa-refresh fa-spin mr-2");
            $('#bntLoading').attr("disabled", false);
            $('#bntLoadingI').removeClass("fa fa-refresh fa-spin mr-2");
            var emailUser = $("#user_registration_form_email").val();
            $.ajax({
                type: form.attr('method'),
                url: form.attr('action'),
                data: form.serialize()
            }).done(function (data) {
                spinner.hide();
                // Optionally alert the user of success here...
                //alert(JSON.stringify(data));
                $('#authorization_form_user').html(selectUser(data.users, emailUser))
                $('#success').html('<div class="alert alert-success" role="alert"><strong>Succès  ! </strong>' + data.message + '</div>')
                swal("Félicition!", data.message, "success");
                $('#bntLoadingAjax').attr("disabled", false);
                $('#bntLoadingIAjax').removeClass("fa fa-refresh fa-spin mr-2");
                form[0].reset();
            }).fail(function (data) {
                spinner.hide();
                $('#success').html('<div class="alert alert-danger" role="alert"><strong>Erreur !</strong>Une erreur s\'est produite veuillez réessayer</div>')
                swal("ERROR!", "Une erreur s\'est produite veuillez réessayer", "error");
                // Optionally alert the user of an error here...
                $('#bntLoadingAjax').attr("disabled", false);
                $('#bntLoadingIAjax').removeClass("fa fa-refresh fa-spin mr-2");
            });
        }
    })
});
$(function () {
    $('.generateUUID').on('click', function (e) {
        e.preventDefault();
        spinner.show();
        var userID = uuid();
        $('#authorization_form_apiKey').val(userID);
        spinner.hide();
    });
});