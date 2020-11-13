/* jshint ignore:start */
define(['jquery', 'jqueryui', 'local_magisterelib/jtable'], function($) {
    return {
        init: function(ajaxUrl, arrayStrs) {
            // Prepare jTable
            $('#MyBadgesTable').jtable({
                title: '',
                paging: false,
                selecting: false,
                multiselect: false,
                selectingCheckboxes: false,
                sorting: true,
                defaultSorting: 'firstname ASC',
                jqueryuiTheme: true,
                defaultDateFormat: 'dd/mm/yy',
                gotoPageArea: 'none',
                selectOnRowClick: false,
                actions: {
                    listAction: function(postData, jtParams) {
                        return $.Deferred(function($dfd) {
                            $.ajax({
                                url: ajaxUrl + '?action=list&si=' + jtParams.jtStartIndex
                                    + '&ps=' + jtParams.jtPageSize
                                    + '&so=' + jtParams.jtSorting,
                                type: 'POST',
                                dataType: 'json',
                                data: postData,
                                success: function(data) {
                                    $dfd.resolve(data);
                                    viewMoreDescription();
                                    $(".delete-badge-btn").on('click', function(e) {
                                        e.preventDefault();
                                        openConfirmDialog(e);
                                    });
                                },
                                error: function() {
                                    $dfd.reject();
                                }
                            });
                        });
                    }
                },
                fields: {
                    id: {
                        title: 'id',
                        key: true,
                        width: '0%',
                        create: false,
                        edit: false,
                        list: false
                    },
                    name: {
                        title: arrayStrs['name'],
                        width: '29%',
                        columnResizable: false,
                        listClass: "jt_name",
                        create: false,
                        edit: false,
                        list: true
                    },
                    description: {
                        title: arrayStrs['description'],
                        width: '35%',
                        columnResizable: false,
                        listClass: "jt_description",
                        create: false,
                        edit: false,
                        list: true,
                        sorting: false
                    },
                    dateobtained: {
                        title: arrayStrs['dateobtained'],
                        width: '10%',
                        columnResizable: false,
                        type: 'date',
                        listClass: "jt_dateobtained",
                        create: false,
                        edit: false,
                        list: true,
                        sorting: false
                    },
                    visibility: {
                        title: arrayStrs['visibility'],
                        width: '10%',
                        columnResizable: true,
                        listClass: "jt_visibility",
                        create: false,
                        edit: false,
                        list: true,
                        sorting: false
                    },
                    actions: {
                        title: arrayStrs['actions'],
                        width: '16%',
                        columnResizable: false,
                        listClass: "jt_actions",
                        create: false,
                        edit: false,
                        list: true,
                        sorting: false
                    },
                }
            });

            // Load person list from server
            $('#MyBadgesTable').jtable('load');

            /**
             * Fonction qui cree un lien "Voir plus" dans la description d'un badge
             */
            function viewMoreDescription() {
                var maxLength = 50;
                $(".show-read-more").each(function() {
                    var myStr = $(this).text();
                    if ($.trim(myStr).length > maxLength) {
                        var newStr = myStr.substring(0, maxLength);
                        var removedStr = myStr.substring(maxLength, $.trim(myStr).length);
                        $(this).empty().html(newStr);
                        $(this).append(' <a href="javascript:void(0);" class="view-more">... Voir plus</a>');
                        $(this).append('<span class="more-description">' + removedStr + '</span>');
                    }
                });
                $(".view-more").click(function() {
                    $(this).siblings(".more-description").contents().unwrap().slideDown("slow");
                    $(this).remove();
                });
            }


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
                                e.currentTarget.form.submit();
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