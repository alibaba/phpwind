/*
 * PHPWind WindEditor Plugin
 * @Copyright 	: Copyright 2011, phpwind.com
 * @Descript	: 商品导购插件
 * @Author		: dongyong.ydy@alibaba-inc.com 
 * @Depend		: jquery.js(1.7 or later)
 * $Id: windeditor.js 4472 2012-02-19 10:41:01Z chris.chencq $			:
 */
;(function ( $, window, undefined ) {
	var WindEditor = window.WindEditor;

	var pluginName = 'insertTao',
		dialog = $('\
			<div class="edit_menu">\
				<div class="edit_menu_music">\
					<div class="edit_menu_top"><a href="" class="edit_menu_close">关闭</a><strong>推广位</strong></div>\
					<div class="edit_menu_cont">\
						<dl class="cc">\
							<dt>文字：</dt>\
							<dd><input type="text" class="input length_5" id="J_input_tao_title"></dd>\
						</dl>\
						<dl class="cc">\
                            <dt>类型：</dt>\
                            <dd>\
                            <label><input name="button_type" type="radio" value="购买" checked="checked">购买</label>\
                            <label><input name="button_type" type="radio" value="下载">下载</label>\
                            <label><input name="button_type" type="radio" value="去看看">去看看</label>\
                            <label><input name="button_type" type="radio" value="抢">抢</label>\
                            </dd>\
                        </dl>\
                        <dl class="cc">\
							<dt>价格：</dt>\
							<dd><input type="text" class="input length_3" id="J_input_tao_price" placeholder="例如：￥50、免费、等等。"></dd>\
						</dl>\
						<dl class="cc">\
							<dt>链接：</dt>\
							<dd><input type="text" class="input length_5" id="J_input_tao_link"></dd>\
						</dl>\
                        <dl class="cc">\
							<dt>图片：</dt>\
							<dd><canvas id="imgBrowser" width="160" height="160" style="display:none" />\
                                <input type="file" style="display:none"  accept="image/png,image/jpeg" onchange="fileSelectEventHandle(event,\'imgBrowser\')" name="Filename" id="Filename" /><input type="button" onclick="Filename.click()" value="选择文件" />(160*160)</dd>\
						</dl>\
					</div>\
					<div class="edit_menu_bot">\
						<button type="button" class="edit_menu_btn">确定</button><button class="edit_btn_cancel" type="button">取消</button>\
					</div>\
				</div>\
			</div>');

	WindEditor.plugin(pluginName,function() {
		var _self = this;
		var editorDoc = _self.editorDoc = _self.iframe[0].contentWindow.document,
			plugin_icon = $('<div class="wind_icon" data-control="'+ pluginName +'"><span class="'+ pluginName +'" title="插入推广"></span></div>').appendTo(  _self.pluginsContainer  );
			plugin_icon.on('click',function() {
				if($(this).hasClass('disabled')) {
					return;
				}
				if(!$.contains(document.body,dialog[0]) ) {
					dialog.appendTo( document.body );
				}
				_self.showDialog(dialog);
			});

            dialog.find()

			//弹窗的关闭事件
			dialog.find('a.edit_menu_close,button.edit_btn_cancel').on('click',function(e) {
				e.preventDefault();
				_self.hideDialog();
			});

			var img_path = _self.options.editor_path + 'themes/' + _self.options.theme + '/';

            //插入
			dialog.find('.edit_menu_btn').on('click',function(e) {
				e.preventDefault();

                var title = $('#J_input_tao_title').val().replace(/,/g,'');
                var link = $('#J_input_tao_link').val();
                var price = $('#J_input_tao_price').val().replace(/,/g,'');
                if( title.length<2 ) {
                    alert('文字内容不能为空！');
                    return;
                }
                if( price.length<1 ){
                    alert('价格不能为空！');
                    return;
                }
                if( link.indexOf('http://')!== 0 ) {
                    alert('地址不正确，请重新输入');
                    return;
                } 
                if( $("#Filename").val()=="" ){
                    alert('请选择文件');
                    return;
                }
                var button_type=$("input[name=button_type]:checked").val();

                //first upload image
                var formData = new FormData();
                formData["enctype"]="multipart/form-data";
                formData.append('fid',IMAGE_CONFIG.postData.fid);
                formData.append('uid',IMAGE_CONFIG.postData.uid);
                formData.append('Filedata', new Blob([btoa('.')],{type:'image/jpeg'}),'t.jpg');
                formData.append('Filename', document.getElementById("imgBrowser").toDataURL('image/jpeg') );
                $.ajax({
                    url:ATTACH_CONFIG.uploadUrl.replace('a=dorun','a=dotao'),
                    type:'post',
                    dataType:'json',
                    timeout: 3000,
                    data:formData,
                    processData:false,
                    contentType:false,
                    cache:false
                }).done(function(ret){
                    //console.log(ret)
                    if( ret.state=='success' ){
                        var _html='<dl class="taobaoCanvas">\
                    <dt><img src="'+ret.data.path+'" /></dt>\
                    <dd class="title">'+title+'</dd>\
                    <dd class="price">'+price+'</dd>\
                    <dd><span class="go"><a href="'+link+'">'+button_type+'</a></span></dd>\
                    <dd style="clear:both;"></dd>\
                    </dl>';
                        //$('#mainForm').append('<input type="hidden" name="flashatt['+ret.data.aid+'][desc]" />');
                        //
                        _self.insertHTML(_html);
                        _self.hideDialog();
                    }else{
                        alert(ret.message[0]);
                        _self.hideDialog();
                    }
                });

			});

			function wysiwyg() {
                var reg = /\[tao=([^,]+),([^,]+),([^,]+),([^,]+),([^,]+)\]\[\/tao\]/ig;
				var	html = $(editorDoc.body).html();
				html = html.replace(reg,function(all, $1, $2, $3, $4, $5) {
                    return '<dl class="taobaoCanvas">\
                    <dt><img src="'+decodeURIComponent($5)+'" /></dt>\
                    <dd class="title">'+$1+'</dd>\
                    <dd class="price">'+$3+'</dd>\
                    <dd><span class="go"><a href="'+decodeURIComponent($2)+'">'+$4+'</a></span></dd>\
                    <dd style="clear:both;"></dd>\
                    </dl>';
				});
				$(editorDoc.body).html(html);
			}

			//加载插件时把ubb转换成可见即所得
			$(_self).on('ready',function() {
    			wysiwyg();
    		});

			$(_self).on('afterSetContent.' + pluginName,function(event,viewMode) {
				wysiwyg();
			});

			//切换成代码模式或者提交时
			$(_self).on('beforeGetContent.' + pluginName,function() {
				$(editorDoc.body).find('.taobaoCanvas').each(function() {
			        var img     = $(this).children('dt').children('img').attr('src');
                    var title   = $(this).children('.title').text().replace(/,/g,'');
                    var price   = $(this).children('.price').text().replace(/,/g,'');
                    var link    = $(this).children('dd').children('.go').children('a').attr('href');
                    var btn    = $(this).children('dd').children('.go').children('a').text();
                    $(this).replaceWith('[tao='+title+','+encodeURIComponent(link)+','+price+','+btn+','+encodeURIComponent(img)+'][/tao]');
                });
			});

	});


})( jQuery, window);
            
function fileSelectEventHandle(e, canvasId){
    if( window.File && window.FileList && window.FileReader && window.Blob ){
        var reader = new FileReader();
        reader.onload = function(event){
            var canvas = document.getElementById(canvasId);
            canvas.style.display = 'inline';
            var context = canvas.getContext('2d');
            var imageObj = new Image();

            imageObj.onload = function() {

                // draw cropped image
                var sourceX = 0;
                var sourceY = 0;
                var sourceWidth = imageObj.width;
                var sourceHeight = imageObj.height;
                var scale = imageObj.width>imageObj.height?imageObj.height/canvas.height:imageObj.width/canvas.width;
                var destWidth = sourceWidth/scale;
                var destHeight = sourceHeight/scale;
                var destX = 0;
                var destY = 0;

                context.drawImage(imageObj, sourceX, sourceY, sourceWidth, sourceHeight, destX, destY, destWidth, destHeight);
            };
            imageObj.src = event.target.result;
        }
        reader.readAsDataURL(e.target.files[0]);
    }else{
        alert('不支持html5,此功能不能使用,请升级浏览器。');
    }
}
//end

