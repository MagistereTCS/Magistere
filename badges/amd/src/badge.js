/* jshint ignore:start */
define(['jquery', 'jqueryui'], function($) {
    return {
        init: function() {

            $(".delete-badge-btn").on('click', function(e) {
                e.preventDefault();
                openConfirmDialog(e);
            });

            /**
             * Fonction qui permet de créer un dialog de confirmation avant validation de suppression de badge.
             *
             * @param {EventHandlerNonNull} e
             */
            function openConfirmDialog(e) {
                $("#dialog_confirm").html(
                    "Souhaitez-vous réellement supprimer ce badge de votre profil ?\n" +
                    "Cette opération est définitive."
                );
                $("#dialog_confirm").dialog({
                    width: 400,
                    title: "Suppression de badge",
                    draggable: "false",
                    modal: true,
                    resizable: false,
                    closeOnEscape: false,
                    closeText: 'Fermer',
                    buttons: [
                        {
                            text: "Oui",
                            id: "btValidate",
                            click: function() {
                                $(this).dialog("close");
                                e.target.form.submit();
                            }
                        },
                        {
                            text: "Non",
                            id: "btCancel",
                            click: function() {
                                $(this).dialog("close");
                            }
                        }]
                });
            }
        }
    };
});