function hasClass(element, cls) {
    return (' ' + element.className + ' ').indexOf(' ' + cls + ' ') > -1;
}

function scrollTo(hash) {
    location.hash = "#" + hash;
}

function getTableColumnValues(userColumn) {
    var columnValues = [];
    var sortedCol = userColumn;
    var table = document.getElementById("authorTable");

    // if no column specified, look for sorted one:
    if (!userColumn || parseInt(userColumn, 10) == 0) {
	for (var i = 0, row; row = table.rows[i]; i++) {
	    // iterate through rows
	    // rows would be accessed using the "row" variable assigned in the
	    // for loop
	    for (var j = 0, col; col = row.cells[j]; j++) {
		// iterate through columns
		// columns would be accessed using the "col" variable assigned
		// in the for loop
		// sorttable_sorted
		if (hasClass(table.rows[i].cells[j], "sorttable_sorted")) {
		    sortedCol = parseInt(j, 10);
		    // alert(sortedCol);
		    break;
		}
	    }
	}

    }
    for (var i = 0, row; row = table.rows[i]; i++) {
	// iterate through rows
	// rows would be accessed using the "row" variable assigned in the for
	// loop
	for (var j = 0, col; col = row.cells[j]; j++) {
	    // iterate through columns
	    if (parseInt(j, 10) != parseInt(sortedCol, 10)) {
		// console.log("not my column " + j + " <> "+userColumn);
		continue;
	    } else {
		var value = (table.rows[i].cells[j]).innerHTML;
		// console.log("new correct value " + value);
		columnValues.push(value);
	    }
	}
    }
    return columnValues;
}

function traceGraph() {

    var graphCanvasId = "graphCanvas";
    // clear canvas:
    var graphCanvas = document.getElementById(graphCanvasId);
    var ctx = graphCanvas.getContext("2d");
    // Store the current transformation matrix
    ctx.save();

    // Use the identity matrix while clearing the canvas
    ctx.setTransform(1, 0, 0, 1, 0, 0);
    ctx.clearRect(0, 0, graphCanvas.width, graphCanvas.height);

    // Restore the transform
    ctx.restore();

    var colArray = getTableColumnValues(0);
    if (!colArray || parseInt(colArray.length, 10) == 0) {
	// No sorted col found
	alert("Please select any column head before tracing graph. Thanks!");
	return;
    }

    var authorArray = getTableColumnValues(1);
    authorArray.shift();
    // alert(colArray.toSource() + authorArray.toSource());

    // get column head
    var header = colArray.shift();
    var graphTitle = header.substring(0, header.indexOf("<"));
    // alert(header.substring(0,header.indexOf("<")));
    // console.log(header);
    var barChartData = {

	labels : authorArray,
	datasets : [ {
	    fillColor : "rgba(151,187,205,0.5)",
	    strokeColor : "rgba(151,187,205,1)",
	    data : colArray
	} ]

    }

    var barChartOptions = {};// = newopts;
    barChartOptions.graphTitle = graphTitle;
    barChartOptions.inGraphDataShow = true;
    barChartOptions.datasetFill = true;

    var myLine = new Chart(ctx).HorizontalBar(barChartData, barChartOptions);

    var pieChartData = [];
    var sum = colArray.reduce(function(pv, cv) {
	return pv + cv;
    });
    // var sum = times.reduce(function(a, b) { return a + b });
    var avg = sum / colArray.length;

    for (var i = 0, author; author = authorArray[i]; i++) {
	var percent = parseFloat(colArray[i]) * 100 / parseFloat(sum);
	var newColor = '#' + Math.floor(Math.random() * 16777215).toString(16);
	console.log(percent + " = " + colArray[i] + "*" + 100 + " / " + sum);
	pieChartData.push({
	    value : percent,
	    color : newColor,
	    title : authorArray[i]
	});
    }

    var pieChartOptions = {};// = newopts;
    pieChartOptions.graphTitle = graphTitle;
    pieChartOptions.inGraphDataShow = true;
    pieChartOptions.datasetFill = true;

    var pieGraphCanvas = document.getElementById("pieGraphCanvas");
    var pieCtx = pieGraphCanvas.getContext("2d");
    var pieGraph = new Chart(pieCtx).Pie(pieChartData, pieChartOptions);

    scrollTo(graphCanvasId);

}

var newopts = {
    inGraphDataShow : true,
    datasetFill : true,
    scaleLabel : "<%=value%>",
    scaleTickSizeRight : 5,
    scaleTickSizeLeft : 5,
    scaleTickSizeBottom : 5,
    scaleTickSizeTop : 5,
    scaleFontSize : 12,
    canvasBorders : true,
    canvasBordersWidth : 3,
    canvasBordersColor : "black",
    graphTitle : "Graph Title",
    graphTitleFontFamily : "'Arial'",
    graphTitleFontSize : 24,
    graphTitleFontStyle : "bold",
    graphTitleFontColor : "#666",
    graphSubTitle : "Graph Sub Title",
    graphSubTitleFontFamily : "'Arial'",
    graphSubTitleFontSize : 18,
    graphSubTitleFontStyle : "normal",
    graphSubTitleFontColor : "#666",
    footNote : "Footnote for the graph",
    footNoteFontFamily : "'Arial'",
    footNoteFontSize : 8,
    footNoteFontStyle : "bold",
    footNoteFontColor : "#666",
    legend : true,
    legendFontFamily : "'Arial'",
    legendFontSize : 14,
    legendFontStyle : "normal",
    legendFontColor : "#666",
    legendBlockSize : 15,
    legendBorders : true,
    legendBordersWidth : 1,
    legendBordersColors : "#666",
    yAxisLeft : true,
    yAxisRight : false,
    xAxisBottom : true,
    xAxisTop : false,
    yAxisLabel : "Y Axis Label",
    yAxisFontFamily : "'Arial'",
    yAxisFontSize : 8,
    yAxisFontStyle : "normal",
    yAxisFontColor : "#666",
    xAxisLabel : "pX Axis Label",
    xAxisFontFamily : "'Arial'",
    xAxisFontSize : 16,
    xAxisFontStyle : "normal",
    xAxisFontColor : "#666",
    yAxisUnit : "Y Unit",
    yAxisUnitFontFamily : "'Arial'",
    yAxisUnitFontSize : 8,
    yAxisUnitFontStyle : "normal",
    yAxisUnitFontColor : "#666",
    annotateDisplay : true,
    spaceTop : 0,
    spaceBottom : 0,
    spaceLeft : 0,
    spaceRight : 0,
    logarithmic : false,
    // showYAxisMin : false,
    rotateLabels : "smart",
    xAxisSpaceOver : 0,
    xAxisSpaceUnder : 0,
    xAxisLabelSpaceAfter : 0,
    xAxisLabelSpaceBefore : 0,
    legendBordersSpaceBefore : 0,
    legendBordersSpaceAfter : 0,
    footNoteSpaceBefore : 0,
    footNoteSpaceAfter : 0,
    startAngle : 0,
    dynamicDisplay : true
}