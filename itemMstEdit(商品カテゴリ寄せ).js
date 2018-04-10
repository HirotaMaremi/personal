import Vue from 'vue';
import axios from 'axios';
import 'babel-polyfill'; // TODO: 削除

new Vue({
    el: '#js-admin__edit-area',
    delimiters: ['${', '}'],
    data: {
        findingProductId: '',
        pcrs: [],
    },
    mounted: async function () {
        const productCategoryId = document.getElementById('js-admin__edit-area').dataset.productCategoryId;
        const url = Routing.generate('app_item_list_prdct_rltn', {id: productCategoryId});
        const res = await axios.get(url);
        if( res.data.count )
            this.pcrs = res.data.pcrs;
    },
    methods: {
        findAddProduct: async function () {
            const url = Routing.generate('app_itm_mst_find_category', {id: this.findingProductId});
            const res = await axios.get(url);
            const product = res.data.product;
            if ( res.data.status )
                this.pcrs.push({id: 0, product: product});
            else
                alert("Not Found ProductId:"+this.findingProductId);
            this.findingProductId = '';
        },
        removeProduct: function (pcr) {
            const idx = this.pcrs.indexOf(pcr);
            this.pcrs.splice(idx, 1);
        },
    }
});

window.addEventListener('load', function (event) {
    //イメージローダーのサムネイルの表示と削除ボタンの挙動
    let imgArea = document.getElementById("js-uploader_uploadedImageWrap");
    const deleteThumbVal = function(){
        imgArea.innerHTML = "";
        const deleteData  = document.getElementById("itm_mst_image_imageDelete");
        deleteData.value = 1;
    };

    document.getElementById("js-uploader_deleteImg").addEventListener(("click"), function () {
        deleteThumbVal();
    });

    // 同一のデータを繰り返し読みたい時用に　ブラウザが保持しているファイル名を消す
    document.getElementById("itm_mst_image_imageFile_file").addEventListener(("click"), function () {
        this.value = null;
    });
    document.getElementById("itm_mst_image_imageFile_file").addEventListener(("change"), function (event) {
        event.preventDefault();
        let file = this.files[0];

        // 画像以外は処理を停止
        if (! file.type.match('image.*')) {
            deleteThumbVal();
            return;
        }
        // 画像表示
        var reader = new FileReader();
        reader.onload = function() {
            // let img_dom = imgArea.children[0];
            // img_dom.setAttribute("src", reader.result);　変更だとeditイベントが拾ってしまうので要素を消して足す。
            var img = document.createElement("img");
            img.setAttribute("src",reader.result);
            img.setAttribute("title",reader.result);
            imgArea.innerHTML = "";
            imgArea.appendChild(img);
        };
        reader.readAsDataURL(file);
    });
});