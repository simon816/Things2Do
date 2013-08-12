var queryPage="/fetchresults.php"
var geoObj=null

function setGeoObject(object) {
    geoObj=object
}

function getLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(setGeoObject)
    }
}

function sendSearch(boxid) {
    var sendQuery=$.trim($("#"+boxid).val())
    if (sendQuery!=null && sendQuery!="") {
        //Delete all cards and possible error
        cardRemoveAll()
        showError(false)
        showResults(false)
        showLoading(true)

        if (!geoObj) {
            geoObj={
                coords: {
                    latitude:"",
                    longitude:""
                }
            }
        }

        $.ajax({url:queryPage, data:{q:sendQuery, lat:geoObj.coords.latitude, lon:geoObj.coords.longitude}, dataType:'json',
            success:function(data,textStatus) {
                // data is always a JSON object, otherwise it will fail
                showResults(true)
                if (!data.error) {
                    var total = data.length
                    if (total > 24) total = 24 // limit to 24. TODO: Never send more than 24 results
                    for (var i=0; i<total; i++) {
                        // TODO: Add cards
                    }
                }
                else {
                    // when the error is in the PHP code
                    // TODO: Handle PHP caught errors
                    showError(true)
                    showResults(false)
                }
            },
            error:function(jqXHR, status, error) {
                // when status is not 2xx or not valid JSON
                if (status=="parsererror") {
                    // TODO: Handle JSON parse errors
                }
                showError(true)
                showResults(false)
            },
            timeout:30000, // 30 Seconds
            complete:function() {
                showLoading(false)
            }
        }) // end AJAX
    }
    else {
        return -1 // no search query entered
    }
}
