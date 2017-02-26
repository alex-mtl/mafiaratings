//------------------------------------------------------------------------------------------
// localization
//------------------------------------------------------------------------------------------
function l()
{
	if (arguments.length == 0)
	{
		return _l["UnknownError"];
	}
	
	var result = _l[arguments[0]];
	if (result === undefined)
	{
		return _l["UnknownError"];
	}
	
	for (var i = 1; i < arguments.length; ++i)
	{
		result = result.replace(new RegExp('\\{' + i + '\\}', 'g'), arguments[i]);
	}
	return result;
} // l()

function handleError(e)
{
	if (typeof e.stack != "undefined")
		console.log(e.stack);
	dlg.error(e);
} // handleError(e)

var dlg = new function()
{
	var _lastId = 0;
	
	this.custom = function(text, title, width, buttons, onClose)
	{
		var parentElem = $("#dlg");
		var id = 'dlg' + _lastId;
		++_lastId;
		
		if (typeof width != "number")
		{
			width = parseInt(width);
			if (isNaN(width) || width <= 0)
			{
				width = 500;
			}
		}
		
		parentElem.append('<div id="' + id + '" title="' + title + '">' + text + '</div>');
		var elem = $('#' + id);
		//elem.html(text);
		return elem.dialog(
		{
			modal: true,
			resizable: false,
			hide: {effect: 'fade', duration: 500},
			show: {effect: 'fade', duration: 500},
			'width': width,
			close: function()
			{
				if (typeof onClose != "undefined") onClose();
				elem.remove();
				--_lastId;
			},
			'buttons': buttons
		});
	}

	this.onScreen = function()
	{
		return _lastId > 0;
	}

	this.curId = function()
	{
		return _lastId - 1;
	}

	this.close = function(dlgId)
	{
		if (typeof dlgId == "undefined")
		{
			dlgId = _lastId - 1;
		}
		if (dlgId >= 0)
		{
			$("#dlg" + dlgId).dialog("close");
		}
	}

	this.error = function(text, title, width, onClose)
	{
		if (typeof title != "string")
		{
			title = l("Error");
		}
		return dlg.custom(text, title, width, 
		{
			ok: { id:"dlg-ok", text: l("Ok"), click: function() { $(this).dialog("close"); } }
		}, onClose);
	}

	this.info = function(text, title, width, onClose)
	{
		if (typeof title != "string")
		{
			title = l("Information");
		}
		return dlg.custom(text, title, width, 
		{
			ok: { id:"dlg-ok", text: l("Ok"), click: function() { $(this).dialog("close"); } }
		}, onClose);
	}

	this.yesNo = function(text, title, width, onYes, onNo)
	{
		if (typeof title != "string")
		{
			title = l("Attention");
		}
		return dlg.custom(text, title, width, 
		{
			yes: { id:"dlg-yes", text: l("Yes"), click: function() { $(this).dialog("close"); if (typeof onYes != "undefined") onYes(); } },
			no: { id:"dlg-no", text: l("No"), click: function() { $(this).dialog("close"); if (typeof onNo != "undefined") onNo(); } }
		});
	}

	this.okCancel = function(text, title, width, onOk, onCancel)
	{
		if (typeof title != "string")
		{
			title = l("Attention");
		}
		return dlg.custom(text, title, width, 
		{
			ok: { id:"dlg-ok", text: l("Ok"), click: function() { $(this).dialog("close"); if (typeof onOk != "undefined") onOk(); } },
			cancel: { id:"dlg-cancel", text: l("Cancel"), click: function() { $(this).dialog("close"); if (typeof onCancel != "undefined") onCancel(); } }
		});
	}
	
	this.form = function(formPage, onSuccess, width)
	{
		var id = null;
		function formCommited(obj)
		{
			if (id != null)
			{
				$(id).dialog("close");
			}
			if (typeof onSuccess != "undefined")
			{
				onSuccess(obj);
			}
		}
		
		function formLoaded(text, title)
		{
			id = '#dlg' + _lastId;
			dlg.custom(text, title, width, 
			{
				ok: { id:"dlg-ok", text: l("Ok"), click: function() { commit(formCommited); } },
				cancel: { id:"dlg-cancel", text: l("Cancel"), click: function() { $(this).dialog("close"); } }
			});
		}
		
		if (typeof width != "number")
		{
			width = parseInt(width);
			if (isNaN(width) || width <= 0)
			{
				width = 800;
			}
		}
		html.get(formPage, formLoaded);
	}
} // dlg

var dialogWaiter = new function()
{
	var counter = 0;

	// returning false cancels the operation
	this.start = function()
	{
		++counter;
		setTimeout(function() { if (counter > 0) $("#loading").show(); }, 500);
		return true;
	}
	
	this.success = function()
	{
		if (--counter <= 0)
		{
			counter = 0;
			$("#loading").hide();
		}
	}
	
	this.error = function(message)
	{
		if (--counter <= 0)
		{
			counter = 0;
			$("#loading").hide();
		}
		dlg.error(message);
	}
	
	this.info = function(message, title, onClose)
	{
		dlg.info(message, title, null, onClose);
	}
	
//	this.connected = function(c) { console.log((c?'connected to ':'disconnected from ') + http.host()); }
	this.connected = function(c) {}
} // dialogWaiter

var silentWaiter = new function()
{
	this.start = function() { return true; }
	this.success = function() {}
	this.error = function(message) { console.log(message); }
	this.info = function(message, title, onClose) { onClose(); }
	this.connected = function(c) {}
} // silentWaiter

var http = new function()
{
	var _waiter = dialogWaiter;
	var _host = '';
	var _connected = false;
	
	this.connected = function(c, w)
	{
		var _c = _connected;
		if (typeof c == "boolean" && _connected != c)
		{
			_connected = c;
			if (typeof w == "object")
				w.connected(c)
			else
				_waiter.connected(c);
		}
		return _c;
	}
	
	this.waiter = function(w)
	{
		var _w = _waiter;
		if (typeof w == "object")
		{
			_waiter = w;
		}
		return _w;
	}
	
	this.host = function(host)
	{
		var h = _host;
		if (typeof host == "string" && host != _host)
		{
			_host = host;
			http.connected(false);
		}
		return h;
	}
	
	this.errorMsg = function(response, page)
	{
		if (typeof response.responseText != "undefined" && response.responseText.length > 0)
		{
			return response.responseText;
		}
		return l('URLNotFound', page);
	}

	this.post = function(page, params, onSuccess, onError)
	{
		var w = _waiter;
		page = _host + page;
		if (w.start())
		{
			//setTimeout(function() {
			$.post(page, params).success(function(data, textStatus, response)
			{
				http.connected(true, w);
				var error = onSuccess(response.responseText);
				if (typeof error == "string" && error.length > 0)
				{
					w.error(error);
				}
				else
				{
					w.success();
				}
			}).error(function(response)
			{
				http.connected(false, w);
				var msg = http.errorMsg(response, page);
				w.error(msg);
				if (typeof onError != "undefined")
				{
					onError(msg);
				}
			});
			//}, 3000);
		}
	}
	
	this.get = function(page, onSuccess, onError)
	{
		var w = _waiter;
		page = _host + page;
		if (w.start())
		{
			//setTimeout(function() {
			$.get(page).success(function(data, textStatus, response)
			{
				http.connected(true, w);
				var error = onSuccess(response.responseText);
				if (typeof error == "string" && error.length > 0)
				{
					w.error(error);
				}
				else
				{
					w.success();
				}
			}).error(function(response)
			{
				http.connected(false, w);
				var msg = http.errorMsg(response, page);
				w.error(msg);
				if (typeof onError != "undefined")
				{
					onError(msg);
				}
			});
			//}, 3000);
		}
	}
} // http

var html = new function()
{
	function _success(text, onSuccess)
	{
		var title = "";
		if (text.substring(0, 7) == "<title=")
		{
			var pos = text.indexOf(">");
			if (pos > 0)
			{
				title = text.substring(7, pos);
				text = text.substring(pos + 1);
			}
		}
		
		if (text.indexOf("<ok>", text.length - 4) !== -1)
		{
			text = text.substring(0, text.length - 4);
			return onSuccess(text, title);
		}
		
		if (text != '')
		{
			console.log(text);
		}
		return text;
	}
	
	this.post = function(page, params, onSuccess, onError)
	{
		http.post(page, params, function(text) { return _success(text, onSuccess); }, onError);
	}
	
	this.get = function(page, onSuccess, onError)
	{
		http.get(page, function(text) { return _success(text, onSuccess); }, onError);
	}
} // html

var json = new function()
{
	var _userName = '';

	function _success(text, onSuccess, onError, retry)
	{
		var result = null;
		try
		{
			var obj = jQuery.parseJSON(text);
			if (typeof obj.uname == "string")
			{
				_userName = obj.uname;
			}
			
			if (typeof obj.login != "undefined")
			{
				var html = 
					'<table class="dialog_form" width="100%">' +
					'<tr><td width="140">' + l('UserName') + ':</td><td><input id="lf-name" value="' + _userName + '"></td>' +
					'<tr><td>' + l('Password') + ':</td><td><input type="password" id="lf-pwd"></td>' +
					'<tr><td colspan="2"><input type="checkbox" id="lf-rem" checked> ' + l('remember') +
					'</td></tr></table>';
/*				if (_userName != '')
				{
					html += '<script>$(function(){$("#lf-pwd").focus();});</script>';
				}*/
				
				var d = dlg.okCancel(html, l('Login'), null, function()
				{
					login($('#lf-name').val(), $('#lf-pwd').val(), $('#lf-rem').attr('checked') ? 1 : 0, function()
					{
						retry();
						refr();
					}, onError);
				}, onError);
			}
			else if (typeof obj.error == "string")
			{
				result = obj.error;
				if (typeof onError != "undefined")
					onError(obj.error);
			}
			else if (typeof obj.message == "string")
			{
				http.waiter().info(obj.message, obj.title, function() { if (typeof onSuccess != "undefined") onSuccess(obj); });
			}
			else if (typeof onSuccess != "undefined")
			{
				onSuccess(obj);
			}
		}
		catch (err)
		{
			console.log(text);
			if (typeof err.stack != "undefined")
				console.log(err.stack);
			result = '' + err;
		}
		return result;
	}
	
	this.post = function(page, params, onSuccess, onError)
	{
		http.post(page, params, function(text)
		{
			return _success(text, onSuccess, onError, function() { json.post(page, params, onSuccess, onError); });
		}, onError);
	}
	
	this.get = function(page, onSuccess, onError)
	{
		http.get(page, function(text)
		{
			return _success(text, onSuccess, onError, function() { json.get(page, onSuccess, onError); });
		}, onError);
	}
} // json

function showMenuBar()
{
	var menubar = $("#menubar");
	if (menubar != null)
	{
//		setTimeout(function() {
		menubar.menubar({
			autoExpand: true,
			menuIcon: true,
			buttons: true,
			position: {
				within: $("#demo-frame").add(window).first()
			}
		});
		menubar.show();
//		}, 3000);
	}
} // showMenuBar()

function refr()
{
	var url = document.URL;
	var p = url.indexOf('#');
	if (p >= 0)
		url = url.substr(0, p);
	window.location.replace(url);
} // refr()

function login(name, pwd, rem, onSuccess, onError)
{
	json.post("login_ops.php", { token: "" }, function(token_resp)
	{
		if (typeof rem == "undefined") rem = $('#remember').attr('checked') ? 1 : 0;
		if (typeof pwd == "undefined") pwd = $("#password").val();
		if (typeof name == "undefined") name = $("#username").val();
		if (typeof onSuccess == "undefined") onSuccess = refr;
		
		var token = token_resp.token;
		var rawId = md5(pwd) + token + name;
		var secId = md5(rawId);
		json.post("login_ops.php",
		{
			username: name,
			id: secId,
			remember: rem,
			login: ""
		}, onSuccess, onError);
	}, onError);
} // login(name, pwd, rem, onSuccess)

function logout()
{
	json.post("login_ops.php", { logout: "" }, function() { window.location.replace("/"); });
} // logout()

// function printStackTrace() { console.log((new Error('stack trace')).stack); }