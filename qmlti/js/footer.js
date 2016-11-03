var body = $('#Wrapper').height();
var win = $(window).height();

if (body > win) {
	$("#FooterWrapper").removeClass("navbar-fixed-bottom");
}