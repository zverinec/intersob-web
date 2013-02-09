$(function(){
	var count = 0;
    $('.volume select').ddslick({
		onSelected: function(data){
			if(count++ == 0) return;
			//var pathname = window.location.pathname;
			//if (pathname.substring(0, 6) !== "/" + data.selectedData.value + "/" && pathname.substring(0, 7) != "/admin/" && pathname.substring(0, 7) != "/year/") {
				window.location = "/" + data.selectedData.value + "/";	
			//}
		}
	});
	
});