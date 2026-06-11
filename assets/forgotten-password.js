import './styles/forgotten-password.css';
import 'smartwizard/dist/css/smart_wizard_all.min.css'
import smartWizard from 'smartwizard'



$(document).ready(function() {
    $('input[type=radio][name=moyen]').change(function() {
        var val = this.value;
        if(val == 1){
            $('#mail').show();
            $("#resetInputEmail").attr('required', '');    
            $('#phone').hide();
            $("#resetInputPhone").removeAttr('required'); 

            $('#choix').html('Mail');
        }else{
            $('#mail').hide();
            $("#resetInputEmail").removeAttr('required'); 
            $('#phone').show();
            $("#resetInputPhone").attr('required', ''); 
            
            $('#choix').html('Numéro de telephone');
        }
    });

    
    let wizardForm = $('#smartwizard');
    wizardForm.smartWizard({
        theme: 'arrows',
        transition: {
            animation: 'fade', 
            speed: '400',
            easing:''
        },
        lang: {
            next: 'Suivant',
            previous: 'Précédent'
        },
    });

    var ajaxInvoke = false;
    var error = false;
    var variable = "";
    var stop = false;
    wizardForm.on('leaveStep', function(e, anchorObject, stepNumber, stepDirection) {
        var moyen = $('#moyen:checked').val();
        var email = $('#email').val();
        var phone = $('#phone').val();

        // stepDirection === 'forward' && 
        if (stepNumber === 0 && ajaxInvoke ==false) {
            if ((moyen == 1 && email === "") || (moyen == 2 && phone === "")) {
                $("#error-step-1").html('Renseignez le champs svp');
                error = true;
                return false;
            }
            if (error === false) {
                if (moyen == 1 && email !== "") { variable = email}
                if (moyen == 2 && phone !== "") { variable = phone}
                var urlFirst = Routing.generate('app_forgotten_password_code', {moyen:moyen,variable:variable});
                //ajaxInvoke = true;
                $.ajax({
                    method: 'GET',
                    url: urlFirst,
                    async: false,
                    success : function (response) {
                        if (response === false) {
                            $("#error-step-1").html('Ceci n\'existe pas dans notre base');
                            stop = true;
                        }else{ $("#error-step-1").html('');}
                    }
                });
            }
        }
        error = false;
        if (stepNumber === 1 && ajaxInvoke ==false) {
            var token = $('#code').val();
            if (code === "") {
                $("#error-step-2").html('Renseignez le code svp');
                error = true;
                return false;
            }
            if (error === false) {
                var urlSecond = Routing.generate('app_forgotten_password_verif_code', {moyen:moyen,variable:variable,token:token});
                //ajaxInvoke = true;
                $.ajax({
                    method: 'GET',
                    url: urlSecond
                    }).then(function (response) {
                        if (response === false) {
                            $("#error-step-2").html('Le code ne correspond pas');
                            stop = true;
                        }else{ $("#error-step-2").html('');}
                }, function (){
                    //for error
                });
            }
        }

        if(stop){stop = false; return false;};
    });
    
    
})