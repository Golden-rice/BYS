require.config({
	// baseUrl: "/eterm/public/js/lib", 
　paths: {
 			"jquery": "lib/jquery.min",

			// datatables
			'datatables': "lib/datatables/js/jquery.dataTables.min",
			'datatablesPlugins': 'lib/datatables-plugins/dataTables.bootstrap.min',
			'datatablesResponsive': 'lib/datatables-responsive/dataTables.responsive',

			// datetimepicker
			'datetimepicker': 'lib/datetimepicker/js/bootstrap-datetimepicker',
   }
});

define('dt', ['jquery', 'datatables', 'datatablesPlugins', 'datatablesResponsive', 'datetimepicker'], function ($, dts, dtp, dtr, dtp){



});