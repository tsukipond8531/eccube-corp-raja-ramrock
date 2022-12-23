<script>
    $(function() {
        var index = 6;
        $('table th').each(function(i) {
            if($(this).text().match(/{{'admin.product.stock'|trans}}/)){
                index = i;
            }
        });
        $('table tr').each(function(i) {
            if (i != 0) {
                $elem = $('#p' + i);
                $elem2 = $('#pr' + i);
                if($elem.length !== 0){
                    $('td:eq('+index+')', this).after('<td class="align-middle"><a href="'+ $elem.text() +'"><button type="button" class="btn page-link text-dark d-inline-block">{{ 'productoption.admin.product.list.option.label.assign'|trans }}</button></a><br><a href="'+ $elem2.text() +'"><button type="button" style="margin-top:2px;" class="btn page-link text-dark d-inline-block">{{ 'productoption.admin.product.list.option.label.sort'|trans }}</button></a></td>');
                    $elem.remove();
                    $elem2.remove();
                }
            } else {
                $elem = $('#productoption_text');
                if($elem.length !== 0){
                    $('th:eq('+index+')', this).after('<th class="border-top-0 pt-2 pb-2">'+ $elem.text() +'</th>');
                    $elem.remove();
                }
            }
        });
    });
</script>