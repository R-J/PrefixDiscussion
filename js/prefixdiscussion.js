/**
 *  @param obj   target       HTML SELECT element to fill
 *  @param int   contentID    Index of the contentArray
 *  @param mixed contentArray Array of strings that should be filled in the SELECT
 */
function fillSelect(target, contentID, contentArray) {
    // reset the content of the SELECT
    target.innerHTML = '<option value="-">-</option>';
    // only if contentID is in the contentArray
    if (contentID in contentArray) {
        // loop through a contentArrays element
        $.each(contentArray[contentID], function (option) {
            // create a new OPTIOn for each entry
            target.options[target.options.length] = new Option(
                contentArray[contentID][option],
                contentArray[contentID][option]
            );
        });
    }
}

$(document).ready(function () {
    // define values to fill into our SELECT element
    var prefixes = new Array();
    prefixes[9] = ['MCC: H2A', 'MCC: H3', 'MCC: H4', 'Halo 4', 'Reach', 'Halo 3'];
    prefixes[11] = ['Unreal Engine', 'Unity', 'UT', 'Minecraft', 'Far Cry', 'Project Spark'];
    prefixes[15] = ['Destiny', 'Call of Duty', 'Smite', 'Evolve'];
    
    // get references to the CategoryDropDown and the prefixes dropdown
    var categorySelect = $('#Form_CategoryID')[0];
    var prefixSelect = $('#Form_Prefix')[0];
    
    // get currently selected category
    var categoryID = categorySelect.value;
    
    // do an initial fill
    fillSelect(prefixSelect, categoryID, prefixes);
    
    $('#Form_CategoryID').on('change', function (e) {
        var categoryID = e.target.value;
        fillSelect(prefixSelect, categoryID, prefixes);
    });
});

