var urlCommands = (function () {
	var self = this;
	this.hash = false;
	this.commands = {};
	this.events = {};

	$(window).bind('hashchange', function() {
		self.init();
	});

	this.init = function() {
		this.hash = window.location.hash;
		this.commands = {};
		if( ~self.hash.indexOf('#!') ) {
			var data = self.hash.replace('#!','').split('/');
			for (var i in data) {
				if (!data.hasOwnProperty(i)) continue;
				i = parseInt(i , 10);
				var key, value;
				key = (!!data[i]) ? data[i] : false;
				if (!key) continue;
				value = (!!data[i+1]) ? data[i+1] : '';
				this.commands[key] = value;
				delete data[i+1];
			}
			$.each(self.commands, function(key, value) {
				if (!!self.events[key]) self.events[key](self.commands[key]);
			});
		}
		return this;
	},
	this.bind = function(name, event) {
		if (typeof event != 'function') return false;
		this.events[name] = event;
		if (!!self.commands[name]) event(self.commands[name]);
	},

	this.urlPush = function(data, reset) {
		reset = reset || false;
		if (typeof window.history.pushState == 'function' && typeof data == 'object') {
			var url = window.location.pathname+'#!';
			$.each(data, function(key, value) {
				self.commands[key] = value;
			});
			if  (reset) self.commands = {};

			$.each(data, function(key, value) {
				url += key+'/'+value+'/';
			});
			$.each(self.commands, function(key, value) {
				if (typeof data[key] == 'undefined' && !data[key]) {
					url += key+'/'+value+'/';
				}
			});
			window.history.pushState(null, url, url);
		}
	}
	this.init();
	return this;
})();