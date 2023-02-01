var btn = document.getElementById('coupon_btn');
btn.addEventListener('click', function(e) {
    copy_to_clipboard('ここの内容をコピーします。');
});

function copy_to_clipboard(value) {
    if(navigator.clipboard) {
        var copyText = value;
        navigator.clipboard.writeText(copyText).then(function() {
            alert('コピーしました。');
        });
    } else {
        alert('対応していません。');
    }
}
