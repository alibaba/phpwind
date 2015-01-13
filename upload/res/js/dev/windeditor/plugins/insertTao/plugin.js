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
					<div class="edit_menu_top"><a href="" class="edit_menu_close">关闭</a><strong>插入商品</strong></div>\
					<div class="edit_menu_cont">\
						<dl class="cc">\
							<dt>标题：</dt>\
							<dd><input type="text" class="input length_5" id="J_input_tao_title"></dd>\
						</dl>\
                        <dl class="cc">\
							<dt>图片：</dt>\
							<dd><canvas id="imgBrowser" width="200" height="200" style="display:none" /><input type="file" style="display:none" onchange="fileSelectEventHandle(event,\'imgBrowser\')" name="Filename" id="Filename" /><input type="button" onclick="Filename.click()" value="选择文件" /></dd>\
						</dl>\
                        <dl class="cc">\
							<dt>价格：</dt>\
							<dd><input type="text" class="input length_3" id="J_input_tao_price"></dd>\
						</dl>\
						<dl class="cc">\
							<dt>地址：</dt>\
							<dd><input type="text" class="input length_5" id="J_input_tao_link"></dd>\
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
			plugin_icon = $('<div class="wind_icon" data-control="'+ pluginName +'"><span class="'+ pluginName +'" title="插入音乐"></span></div>').appendTo(  _self.pluginsContainer  );
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

			//插入网络音乐媒体
			dialog.find('.edit_menu_btn').on('click',function(e) {
				e.preventDefault();

                var title = $('#J_input_tao_title').val();
                var link = $('#J_input_tao_link').val();
                if( title.length<2 ) {
                    alert('请描述一下商品标题');
                    return;
                }
                if( link.indexOf('http')!== 0 ) {
                    alert('商品地址不正确，请重新输入');
                    return;
                }

                //first upload image
                var formData = new FormData();
                formData["enctype"]="multipart/form-data";
                formData.append('fid',IMAGE_CONFIG.postData.fid);
                formData.append('uid',IMAGE_CONFIG.postData.uid);
                formData.append('Filedata', new Blob([btoa('.')],{type:'image/jpeg'}),'t.jpg');
                formData.append('Filename', document.getElementById("imgBrowser").toDataURL('image/jpeg') );
                $.ajax({
                    url:ATTACH_CONFIG.uploadUrl,
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
                        _self.insertHTML('<table class><tbody><tr><td><img src="'+ret.data.path+'" /></td><td valign="top">美女一枚，出售，抄底价。</br>￥56.99</br><input type="button" value="购买" onclick="alert(\"ok\");"></td></tr></tbody></table>');

                        //				_self.insertHTML('<img class="j_editor_tao_content" width="300" height="40" style="border:1px dashed #ccc;display:block;background:#fffeee url('+ img_path +'music_48.png) center center no-repeat;" src="'+ img_path +'blank.gif" data-url="'+ link +'" data-auto="'+ is_autoplay +'"/>').hideDialog();
                        _self.hideDialog();
                    }else{
                        alert(ret.message[0]);
                        _self.hideDialog();
                    }
                });

			});

			function wysiwyg() {
				var reg = /\[mp3=(\d)\]([\s\S]*?)\[\/mp3\]/ig;
				var	html = $(editorDoc.body).html();
				html = html.replace(reg,function(all, $1, $2) {
//					return '<img class="j_editor_tao_content" width="300" height="40" style="border:1px dashed #ccc;display:block;background:#fffeee url('+ img_path +'music_48.png) center center no-repeat;" src="'+ img_path +'blank.gif" data-url="'+ $2 +'" data-auto="'+ $1 +'"/>';
                    _self.insertHTML('<table><tbody><tr><td><img src="http://10.101.81.197:8001/phpwind/upload/attachment/thumb/mini/1501/thread/2_3_3b214088fd537f1.jpg" ></td><td valign="top">美女一枚，出售，抄底价。</br>￥56.99</br><input type="button" value="购买" onclick="alert(\"ok\");"></td></tr></tbody></table>');
            
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
				$(editorDoc.body).find('img.j_editor_music_content').each(function() {
					var url = $(this).data('url'),
						is_autoplay = $(this).data('auto');
					$(this).replaceWith('[mp3='+ is_autoplay +']'+ url +'[/mp3]');
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

