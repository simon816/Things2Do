$(document).ready(function(){

    $("#resultsdiv").hide()

    inputs=$('input[type=text]')
    inputs.bind('keyup', function(e){
        inputs.each(function(i, input) {
            if (input!=e.target) {
                input.value=e.target.value
            }
        })
    })

    $(document.s).bind('submit', function(event){
        event.preventDefault()
        if (sendSearch('homesearch')!=-1) {
            $("#resultsdiv").show()
             makeScroll(function(){
                 $("#home").hide()
                 window.scrollTo(0)
                 location.hash="#";
             })
         }
    })

    $(document.rs).bind('submit', function(event){
        event.preventDefault()
        sendSearch('searchresult')
    })

    $("#logoclick").bind('click', function(event){
        event.preventDefault()
        $("#home").show()
        $('#resultsdiv').hide()
    })

})

function cardRemoveAll() {
    $("div.card, div.smallcard").remove()
}

function showError(show) {
	var errorMessage=$("#errormessage")
	if (show) {
		errorMessage.show()
	}
    else {
		errorMessage.hide()
	}
}

function showResults(show) {
	var resultsBox=$("#resultscontainer")
	if (!show) {
		resultsBox.hide()
	}
    else {
		resultsBox.show()
	}
}

function showLoading(show) {
	var loadGif=$("#loading")
	if (show) {
		loadGif.show()
	}
    else {
		loadGif.hide()
	}
}
