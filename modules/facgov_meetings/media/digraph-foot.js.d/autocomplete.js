digraph.autocomplete.facgovrostermember = {
    source: digraph.url+'_json/autocomplete-facgovrostermember',
    source_definitive: digraph.url+'_json/autocomplete-facgovrostermember-definitive',
    select: function(ui) {
        var $netid = $('#add-roster-member_netid');
        var $textarea = $('#add-roster-member_digraph-body_text');
        var $select = $('#add-roster-member_digraph-body_filter');
        // console.log($body);
        if (ui.item['field_netid']) {
            $netid.val(ui.item['field_netid']);
        }
        if (ui.item['field_body']) {
            $textarea.val(ui.item['field_body']);
            $textarea.trigger('change');
        }
    }
};