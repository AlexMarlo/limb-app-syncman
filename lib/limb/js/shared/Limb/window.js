Limb.namespace('Limb.Window');

Limb.Window = Class.create();

Limb.Window.prototype = {
  initialize: function()
  {
    this.parentWindow = null;
    this.windowName = this._generateName();
    this.onLoadEvents = [];
    this.onUnloadEvents = [];

    if(arguments.length == 0)
      this.window = window;

    // arguments[0] instanceof Window does not work in IE
    if(typeof(arguments[0]) == 'object')
      this.window = win;

    if(arguments.length == 2 || arguments.length == 3)
      this.window = this._createWindow(arguments[0], arguments[1], arguments[2]);

    Limb.events.add_event(this.window, 'load', this.onOpen.bind(this), false);
    Limb.events.add_event(this.window, 'close', this.onClose.bind(this), false);
  },

  getWindowObject: function()
  {
    return this.window;
  },

  centreWindow: function(width, height)
  {
    var newWindowRect = this._getRectInParentCenter(width, height);
    this.setRect(newWindowRect);
  },

  _getRectInParentCenter: function(width, height)
  {
    var windowRect = this.parentWindow.getRect();

    var result = new Limb.Coordinates.Rect();
    result.createWithCenter(windowRect.getCenter(), width, height);

    return result;
  },

  _getDefaultParams: function()
  {
    var width = 150;
    var height = 100;

    var newWindowRect = this._getRectInParentCenter(width, height);

    var params = new Limb.Window.Params();
    params.addParameter('left', newWindowRect.getX());
    params.addParameter('top', newWindowRect.getY());
    params.addParameter('width', width);
    params.addParameter('height', height);

    params.addParameter('scrollbars', 'yes');
    params.addParameter('resizable', 'yes');
    params.addParameter('help', 'no');
    params.addParameter('status', 'yes');

    return params;
  },

  _generateName: function()
  {
    return Math.round(Math.random() * 1000) + '_generate';
  },

  _createWindow: function(href, windowName, createParams)
  {
    this.parentWindow = new Limb.Window();

    if(windowName)
      this.windowName = windowName;

    if(!Limb.isset(createParams))
      createParams = this._getDefaultParams();

    var win = window.open(href, this.windowName, createParams.toString());

    return win;
  },

  getRect: function()
  {
    if(Limb.browser.is_ie)
      return new Limb.Coordinates.Rect(this.window.screenLeft,
                                       this.window.screenTop,
                                       this.window.document.body.clientWidth,
                                       this.window.document.body.clientHeight);
    else
      return new Limb.Coordinates.Rect(this.window.screenX + this.window.outerWidth - this.window.innerWidth,
                                       this.window.screenY + this.window.outerHeight - this.window.innerHeight,
                                       this.window.innerWidth,
                                       this.window.innerHeight);
  },

  setRect: function(rect)
  {
    if(!rect)
      return false;

    this.window.moveTo(rect.getX(), rect.getY());
    this.window.resizeTo(rect.getWidth(), rect.getHeight());

    return true;
  },

  onOpen: function()
  {
    Limb.Window.register(this.windowName, this);

    if(!this.window.limbWindowWidth)
      this.window.limbWindowWidth = this.parentWindow.getRect().getWidth() * 0.85;

    if(!this.window.limbWindowHeight)
      this.window.limbWindowHeight = this.parentWindow.getRect().getHeight() * 0.9;

    this.centreWindow(this.window.limbWindowWidth, this.window.limbWindowHeight);

    this.openHandler();
  },

  onClose: function()
  {
    Limb.Window.remove(this.windowName);

    this.closeHandler();
  },

  openHandler: function() {},
  closeHandler: function() {}
}

Limb.Window.register = function(windowName, win)
{
  if(!Limb.isset(Limb.Window.createdWindows))
    Limb.Window.createdWindows = [];

  Limb.Window.createdWindows[windowName] = win;
}

Limb.Window.remove = function(windowName)
{
  if(!Limb.isset(Limb.Window.createdWindows)||
     !Limb.isset(Limb.Window.createdWindows[windowName]))
    return;

  Limb.Window.createdWindows[windowName] = null;
  delete Limb.Window.createdWindows[windowName];
}

Limb.Window.current = function()
{
 if(!Limb.isset(Limb.Window.createdWindows))
    return null;

  for(var i in Limb.Window.createdWindows)
    if(Limb.Window.createdWindows[i].getWindowObject() == window)
      return Limb.Window.createdWindows[i];

  return null;
}

Limb.Window.Params = Class.create();

Limb.Window.Params.prototype = {
  initialize: function(initArray)
  {
    this.params = initArray || [];
  },

  setParameter: function(name, value)
  {
    this.params[name] = value;
  },

  addParameter: function(name, value)
  {
    if(Limb.isset(this.params[name]))
      return;

    this.setParameter(name, value);
  },

  getParameter: function(name)
  {
     return params[name];
  },

  toString: function()
  {
    var result = '';

    for(var name in this.params)
      result += name + '=' + this.params[name] + ',';

    return result.slice(0, -1);
  },

  toArray: function()
  {
    return this.params;
  }
}

Limb.namespace('Limb.Coordinates');

Limb.Coordinates.Point = Class.create();

Limb.Coordinates.Point.prototype = {
  initialize: function(x, y)
  {
    this.x = x || 0;
    this.y = y || 0;
  },

  setX: function(x)
  {
    this.x = x;
  },

  setY: function(y)
  {
    this.y = y;
  },

  getX: function()
  {
    return this.x;
  },

  getY: function()
  {
    return this.y;
  }
}

Limb.Coordinates.Rect = Class.subclass(Limb.Coordinates.Point, {
  initialize: function(x, y, width, height)
  {
    arguments.callee.nextMethod(this, x, y);

    this.width = width || 0;
    this.height = height || 0;
  },

  setWidth: function(width)
  {
    this.width = width;
  },

  setHeight: function(height)
  {
    this.height = height;
  },

  getWidth: function()
  {
    return this.width;
  },

  getHeight: function()
  {
    return this.height;
  },

  getRight: function()
  {
    return this.x + this.width;
  },

  getBottom: function()
  {
    return this.y + this.height;
  },

  setSize: function(width, height)
  {
    this.width = width || 0;
    this.height = height || 0;
  },

  createWithCenter: function(point, width, height)
  {
    if(!point)
      return;

    this.setSize(width, height);
    this.alignToCenter(point);
  },

  alignToCenter: function(point)
  {
    this.x = point.x - this.width / 2;
    this.y = point.y - this.height / 2;
  },

  getCenter: function()
  {
    return new Limb.Coordinates.Point(this.x + (this.width / 2), this.y + (this.height / 2));
  }
});
