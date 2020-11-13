<div id="search_result" style="width:100%"></div>

<script type="text/javascript">
$(function(){

	if({[{show_add_resource_button}]} == true){
		$('#search_result').append('<input type="button" value="{[{local_cr_add_resource_button_label}]}" id="add_resource_button"/>');

		$('#add_resource_button').click(function(){
			window.location.href = "{[{add_resource_url}]}";
		});
	}

	var firstLoad = true;
	
	$('#search_result').jtable({
		title: "{[{local_cr_jtable_title}]}",
		paging: true,
		pageSize: 20,
		pageSizes: [20, 30, 40],
		selecting: true,
		multiselect: false,
		selectingCheckboxes: "{[{show_checkboxes}]}",
		sorting: true,
		defaultSorting: 'name ASC',
		jqueryuiTheme: true,
		defaultDateFormat: 'dd-mm-yy',
		gotoPageArea: 'none',
		selectOnRowClick: false,
		actions: {
			listAction: function (postData, jtParams) {
				return $.Deferred(function ($dfd) {					
					var formdata = $("#mform1").serialize();

					var startIndex = postData && postData.jtStartIndex;
					
					if(!startIndex){
						startIndex = jtParams.jtStartIndex;
					}
					
					formdata += '&si=' + startIndex + '&ps=' + jtParams.jtPageSize + '&so=' + jtParams.jtSorting;
					
					if("{[{CONTEXT_ID}]}" != ""){
						formdata += '&contextid={[{CONTEXT_ID}]}';
					}

					if($("#id_centralizedresourceid").val() && firstLoad){
						formdata = 'ps=' + jtParams.jtPageSize + '&so=' + jtParams.jtSorting + '&record_id=' + $("#id_centralizedresourceid").val();
						
						$.ajax({
					           type: "POST",
					           url: '{[{url_getpagerecord}]}',
					           dataType: 'json',
					           data: formdata,
					           success: function(data)
					           {
					        	   firstLoad = false;

					        	   var plugin = $('#search_result').data('hik-jtable');

					        	   //use the private function of the jtable's plugin to change
					        	   //the current page. Don't use the _changePage method, because we
					        	   //have to initialize the _currentPageNo attribute ; due to a bug
					        	   //of _changePage because _calculatePageCount method return 0 in our case
					        	   plugin._currentPageNo = data['page'];
					        	   plugin._reloadTable();
					        	   
				               },
				               error: function () {
				                   $dfd.reject();
				               }
				           });
							
					}else{
						$.ajax({
					           type: "POST",
					           url: "{[{url_search}]}",
					           dataType: 'json',
					           data: formdata,
					           success: function(data)
					           {
	                                  $dfd.resolve(data);
                              },
                              error: function () {
                                  $dfd.reject();
                              }
                          });
					}
				});
			}
		},
		fields: {
				id: {
					title: 'id',
					key: true,
					create: false,
					edit: false,
					list: false
				},
				name: {
					title: 'Titre',
					width: '20%',
					create: false,
					edit: false,
					list: true
				},
				label_type: {
					title: 'Type',
					width: '20%',
					create: false,
					edit: false,
					list: true
				},
				domain: {
					title: 'Domaine',
					width: '20%',
					create: false,
					edit: false,
					list: true
				},
				creator: {
					title: 'Créateur',
					width: '10%',
					create: false,
					edit: false,
					list: true
				},
				updatedate: {
					title: 'Modif',
					width: '35%',
					type: 'date',
					create: false,
					edit: false,
					list: true
				},
				action: {
					title: 'Action',
					width: '5%',
					create: false,
					edit: false,
					list: "{[{show_action}]}",
					sorting: false,
					display: function(data){
						if(data.record.action !== null){
							return "<a href=" + data.record.action + ">Modifier</a>";
						}else{
							return '';
						}
					}
				}
		},
        selectionChanged: function (event, data) {
        	var $selectedRows = $('#search_result').jtable('selectedRows');        	
            if($selectedRows.length > 0 ){
                $selectedRows.each(function () {
                    var record = $(this).data('record');
                    var id = record.id;
    				$("#id_centralizedresourceid").val(record.id);
    				displayForcingDownload(record.type);
                });
            }
        },
        rowInserted: function (event, data) {
            if (data.record.id == $("#id_centralizedresourceid").val()) {
                $('#search_result').data('hik-jtable')._selectRows(data.row);
                displayForcingDownload(data.record.type);
            }
        }
		
	})

	if({[{show_add_resource_button}]} == true){
		$('#search_result').append('<br/><input type="button" value="{[{local_cr_add_resource_button_label}]}" id="add_resource_button_down"/>');
	
		
		$('#add_resource_button_down').click(function(){
			window.location.href = "{[{add_resource_url}]}";
		});
	}

	if({[{show_return_button}]} == true){
		
		$('#search_result').append('<input type="button" value="{[{local_cr_return_button_label}]}" id="return_button"/>');

		$('#return_button').click(function(){
			window.location.href = "{[{return_url}]}";
		});
	}

	$("#search_result").jtable("load");
});
function displayForcingDownload(type){
	if(type == "diaporama"){											
		$("#id_display > option[value$='{[{RESOURCELIB_DISPLAY_DOWNLOAD}]}']").hide();
		if($("#id_display").val()=='{[{RESOURCELIB_DISPLAY_DOWNLOAD}]}'){
			$("#id_display").val("0");
		}	
	}else{
		$("#id_display > option[value$='{[{RESOURCELIB_DISPLAY_DOWNLOAD}]}']").show();
	}
};
</script>