$(document).ready(function(){

    $("#resultsdiv").hide()

    inputs=$('input[type=text]')
    inputs.bind('keyup', function(e){
        inputs.val(e.target.value)
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