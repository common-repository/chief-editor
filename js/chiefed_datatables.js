jQuery(document).ready(function($) {
	/*
	jQuery.fn.dataTable.ext.search.push(
    	    function( settings, data, dataIndex ) {
    	    		var dateColumnIdx = 8;
    	        var timeFilter = $('#editor_dashboard_time_filter_id').val();
    	        console.log("Time filter is "+timeFilter);
    	            	        
    	        var date = parseFloat( searchData[dateColumnIdx] ) || 0; // using the data from the 4th column
    	  
    	        
    	        if ( timeFilter )
    	        {
    	            return true;
    	        }
    	        return false;
    	    }
    	);*/
	
	
	var singleShotTable = jQuery('#editor_single_shot_dashboard').DataTable(
		{
	"order" : [ [ 3, 'asc' ]/*, [ 4, 'desc' ]*/ ],
	"pageLength": 100,
    //"lengthChange": false,
    "bInfo": false,
	"columnDefs" : [ {
	    "targets" : [ 0, 1, 2, 7 ],
	    "visible" : false
	} ],
	"searching": false,
	"footerCallback": function ( row, data, start, end, display ) {
	            var api = this.api(), data;
 
	            // Remove the formatting to get integer data for summation
	            var intVal = function ( i ) {
	                return typeof i === 'string' ?
	                    i.replace(/[\$,]/g, '')*1 :
	                    typeof i === 'number' ?
	                        i : 0;
	            };
 
				var colNumberOfPagesIdx = 10;
	            // Total over all pages
	            total = api
	                .column( colNumberOfPagesIdx )
	                .data()
	                .reduce( function (a, b) {
	                    return intVal(a) + intVal(b);
	                }, 0 );
 
	            //var colNumberOfPagesIdx = 9;
	            /*
	            totalBAT =  api
                .column( colNumberOfPagesIdx )
                .data()
                .reduce( function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0 );
	            */
	            
	            /*
	            totalBAT = api
	              .cells( function ( index, data, node ) {
	            	  		console.log(api.row( index ).data()[7].replace(/(<([^>]+)>)/ig,""));
	                        return api.row( index ).data()[7].replace(/(<([^>]+)>)/ig,"") == 'built' ?
	                            true : false;
	                    }, 0 )
	              .data()
	              .reduce( function (a, b) {
	                  return intVal(a) + intVal(b);
	              } );*/
	            
	            
	            
	            // Total over this page
					/*
	            pageTotal = api
	                .column( 4, { page: 'current'} )
	                .data()
	                .reduce( function (a, b) {
	                    return intVal(a) + intVal(b);
	                }, 0 );
 */
	            // Update footer
	            $( api.column( colNumberOfPagesIdx ).footer() ).html(
	                total +' pages'/*+' ('+totalBAT+' en BAT)'*/
	            );
	        }
	}
);
	
    var editorTable = jQuery('#editor_dashboard').DataTable({
	"order" : [ [ 2, 'desc' ]/*, [ 4, 'desc' ]*/ ],
	"columnDefs" : [ {
	    "targets" : [ 0, 7 ],
	    "visible" : false
	} ],
	"pageLength": 100,
	"createdRow": function( row, data, dataIndex ) {
		var raw_status = data[7];
	    if ( raw_status == "bat" ) {
	      $(row).addClass( 'chiefed_bat' );
	    }
	  },
    "processing": true,
    "serverSide": true,
    "ajax":{
        //url : 'http://intranet/wp-content/plugins/chief-editor/chiefed_table_data.php',
        url : chiefed_ajax_object.ajax_url,
        //"chiefed_table_data.php", // json datasource
        type: "post",  // method  , by default get
        //dataSrc : "chiefed_get_table_data",
        /*
        data: {
        		action: 'chiefed_get_table_data',
        		timeframe : $('#editor_dashboard_time_filter_id').find("option:selected").attr('value'),
        },*/
        
        data: function ( d ) {
            return $.extend( {action: 'chiefed_get_table_data'}, d, {
              "timeframe": $('#editor_dashboard_time_filter_id').find("option:selected").attr('value'),
            } );
          },
        
        /*error:  function (xhr, error, thrown) {
            error( xhr, error, thrown );
        }*/
        
        dataSrc: function ( json ) {
            //Make your callback here.
            //alert("Done!");
        		if (json != null && json.data != null){
        			var count = Object.keys(json.data).length;
                    console.log("Data received :) "+count);
                    //swal({title :"Ok", text :'', type :"success",  timer: 1000}).catch(swal.noop);
                    return json.data;
        		} else {
        			return null;	
        		}
        		
            
        }, 
        
        
        error: function(xhr, error, thrown){  // error handling
             //$(".chiefed-grid-error").html("");
             //$("#chiefed-error-panel").append('No data found in the server');
        		console.log(xhr);
             $("#chiefed-error-panel").append(error);
             $("#chiefed-error-panel").append(':<br/>');
             $("#chiefed-error-panel").append(thrown);
             $("#chiefed-error-panel").append('<br/>');
             //$("#chiefed-error-panel").append(chiefed_ajax_object.ajax_url);
             //$("#chiefed-error-panel").append(xhr.'<br/>');
             
             $("#editor_dashboard_processing").css("display","none");
			}
		},
	/*"columns": [
		        { "data": "Revue" },
		        { "data": "Numéro" },
		        { "data": "Catégorie" },
		        { "data": "Sous-catégorie" },
		        { "data": "Article" },
		        { "data": "Auteur" }

		    ],*/
	"dom": '<"toolbar">frtip',
	"footerCallback": function ( row, data, start, end, display ) {
	            var api = this.api(), data;
 
	            // Remove the formatting to get integer data for summation
	            var intVal = function ( i ) {
	                return typeof i === 'string' ?
	                    i.replace(/[\$,]/g, '')*1 :
	                    typeof i === 'number' ?
	                        i : 0;
	            };
 
				var colIdx = 10;
				var rawStatusColIdx = 7; 
	            // Total over all pages
	            total = api
	                .column( colIdx )
	                .data()
	                .reduce( function (a, b) {
	                    return intVal(a) + intVal(b);
	                }, 0 );
 
	            // Total over this page
					
	            pageTotal = api
	                .column( colIdx, { page: 'current'} )
	                .data()
	                .reduce( function (a, b) {
	                    return intVal(a) + intVal(b);
	                }, 0 );
 
	            // Update footer
	            $( api.column( colIdx ).footer() ).html(
	                pageTotal +' pages (Total:'+ total +' pages)'
	            );
	        }
    
	}
);


	// jQuery("div.toolbar").html(generateTimeMenu());
   
    // Event listener to the two range filtering inputs to redraw on input
    jQuery('#editor_dashboard_filter_id').change(function() {
	// alert( jQuery(this).find("option:selected").attr('value') );
	editorTable.draw();
    });
    jQuery('#editor_dashboard_time_filter_id').change(function() {
	 //alert( jQuery(this).find("option:selected").attr('value') );
    	
    	//alert("new time frame");
    /*	swal({
            title: 'Mise à jour',
            text: 'Merci de patienter quelques intants...',                     
            onOpen: () => {
              swal.showLoading()
            }
        }).catch(swal.noop);*/
	editorTable.draw();
    });
    
    
    
    
    /*
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
    //	var chosenUserId = $('#office_dashboard_filter_id').find("option:selected").attr('value');
    	var chosenTime = $('#editor_dashboard_time_filter_id').find("option:selected").attr('value')
    	var timeCondition = true;
    	var now = new Date();
    	now.setHours(0, 0, 0, 0);
    	var startTime = new Date();
    	var endTime = new Date();
    	var startTimestamp = Date.now();// startTime.getTime();
    	var endTimestamp = Date.now();// endTime.getTime();
    	var spontaneous = false;
    	// console.log("now: "+endTimestamp+' chosenTime: '+chosenTime);
    	switch (chosenTime) {
    	case 'today':

    	    startTime.setHours(0, 0, 0);
    	    startTimestamp = startTime.getTime();
    	    endTime.setHours(23, 59, 59);
    	    endTimestamp = endTime.getTime();

    	    break;
    	case 'current_week':
    	
    	    // console.log("current_week: "+startTimestamp);
    	    var monday = new Date(now);
    	    monday.setDate(monday.getDate() - monday.getDay() + 1);
    	    var sunday = new Date(now);
    	    sunday.setDate(sunday.getDate() - sunday.getDay() + 7);
    	    startTimestamp = monday.getTime();
    	    endTimestamp = sunday.getTime();

    	    break;
    	case 'next_week':
    	    var monday = new Date(now);
    	    monday.setDate(monday.getDate() - monday.getDay() + 7);
    	    var sunday = new Date(now);
    	    sunday.setDate(sunday.getDate() - sunday.getDay() + 14);
    	    startTimestamp = monday.getTime();
    	    endTimestamp = sunday.getTime();
    	    break;
    	case 'spontaneous':
    	    spontaneous = true;
    	    
    	    break;
    	default:
    	    //console.log("display all rdv");
    	    startTime.setYear(1970);
    	    startTimestamp = startTime.getTime();
    	    var lastDay = endTime.getDate() + 365;
    	    endTimestamp = new Date(endTime.setDate(lastDay)).getTime();
    	}

    	//console.log(startTime.toUTCString() + " -> " + endTime.toUTCString());

    	var senderId = parseInt(data[0]) || -1; // use data for the age
    	//console.log("spontaneous: " + spontaneous);
    	if ($.isNumeric(data[1])) {

    	    var rdvTimestamp = parseInt(data[1]) || -1; // use data for the age
    	    console.log("rdvTimestamp: " + rdvTimestamp);
    	    if (spontaneous) {
    		 timeCondition = rdvTimestamp < 0;
    	    } else {
    		 timeCondition = rdvTimestamp < 0 || (startTimestamp <= rdvTimestamp && rdvTimestamp <= endTimestamp);
    	    }
    	}
    	var practitionerCondition = 1;//chosenUserId.length == 0 || (!isNaN(chosenUserId) && !isNaN(senderId)) && (chosenUserId == senderId);

    	
    	// var noRDVCondition = rdvTimestamp < 0;

    	if (practitionerCondition && timeCondition) {
    	    //console.log("Display because " + startTimestamp + " <= " + rdvTimestamp + " <= " + endTimestamp);
    	    return true;
    	}
    	return false;
        });
*/

});

function generateTimeMenu() {
	/*
	const startOfMonth = moment().startOf('month');
	const endOfMonth   = moment().endOf('month');*/
	
	var prevMonthFirstDay = new moment().subtract(0, 'months').date(1);
	var prev2MonthFirstDay = new moment().subtract(1, 'months').date(1);
	
	var currentMonthFirstDay = new moment().add(0, 'months').date(1);
	var current2MonthFirstDay = new moment().add(1, 'months').date(1);
	
	var nextMonthFirstDay = new moment().add(1, 'months').date(1);
	var next2MonthFirstDay = new moment().add(2, 'months').date(1);
	
	var noneOption = '<option value="">All</option>';
	
	var firstOption = '<option value="'+prev2MonthFirstDay.unix()+':'+prevMonthFirstDay.unix()+'">'+prev2MonthFirstDay.format('MMMM')+' : '+prev2MonthFirstDay.format('DD-MM-YYYY')+' -> '+prevMonthFirstDay.format('DD-MM-YYYY')+'</option>';
	
	var currentMonth = '<option value="'+currentMonthFirstDay.unix()+':'+current2MonthFirstDay.unix()+'" selected>'+currentMonthFirstDay.format('MMMM')+' : '+currentMonthFirstDay.format('DD-MM-YYYY')+' -> '+current2MonthFirstDay.format('DD-MM-YYYY')+'</option>';
	
	var nextMonth = '<option value="'+nextMonthFirstDay.unix()+':'+next2MonthFirstDay.unix()+'">'+nextMonthFirstDay.format('MMMM')+' : '+nextMonthFirstDay.format('DD-MM-YYYY')+' -> '+next2MonthFirstDay.format('DD-MM-YYYY')+'</option>';
	
	var result = '<select id="chiefeditor_dt_time_select">'+noneOption+firstOption+currentMonth+nextMonth+'</select>';
   
	
	return result;
}
