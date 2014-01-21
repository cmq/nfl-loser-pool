/**
 * Javascript Object-Oriented Development add-on
 * By Kirk Hemmen
 * 
 * This file adds functionality to javascript Function prototype, and extends
 * the root javascript Object into OOObject (Object-Oriented Object) with
 * methods that allow the user to interact with javascript in a way that is
 * closer to classical OO design.  Specifically, the following * public
 * methods are added.
 * 
 * 
 * Function.prototype
 *        inherits:            This function allows you to specify a constructor
 *                             function that your given constructor function will
 *                             inherit from.  Simply call 
 *                             ConstructorFunction.inherits(ParentConstructorFunction)
 *                             and this function will align all of the prototypes
 *                             and private helper members properly.
 *         implements:         This function allows you to specify that your
 *                             constructor function implements a given interface.
 *                             Using this function will attempt to instantiate a
 *                             new object using that constructor function, and
 *                             then run Object.prototype.implement against it.
 *                             Therefore, it's important to note that:
 *                             CONSTRUCTOR FUNCTIONS MUST NOT REQUIRE CONSTRUCTOR
 *                             ARGUMENTS
 * 
 * OOObject.prototype
 *         inherits            This is a simple convenience method that can be
 *                             used within the context of a child class instance
 *                             to run the parent constructor in the child scope.
 *                             So for example, within the child class' constructor
 *                             code, you would simply run:
 *                             this.inherits(ParentClassName[, options])
 *        implements:          This function allows you to test whether or not
 *                             your instantiated object implements a given
 *                             interface.  Interfaces are defined by functions
 *                             whose methods to be implemented exist as empty
 *                             functions in the prototype.  This function can be
 *                             passed a single interface or an array of
 *                             interfaces.  Calling implements will not alter
 *                             anything you pass it, it will simply serve as a
 *                             verification that all of the interface's prototype
 *                             methods have been implemented in your object.
 *        isInterface          This prototype method can be used on any object to
 *                             determine if it is a descendent of the defined
 *                             Interface constructor function.  This is used to
 *                             enforce that only Interface() descendents are
 *                             ".implement()"'d and to aid in the detection of
 *                             Object.isInstanceOf
 *         isInstanceOf        This prototype method is similar to the built-in
 *                             Javascript instanceof operator, except that it will
 *                             also check against interfaces so that any function
 *                             or object that implements an interface is
 *                             considered to be an "instance of" that interface.
 *                             This also works correctly with inherited
 *                             constructor functions/classes.
 *         option              Returns (one parameter) or sets (two parameters)
 *                             the setting of the given name.  All OOObjects have
 *                             a local variable called settings where these values
 *                             can be examined or set using .option()
 *
 * 
 * It is assumed that all objects you intend to take advantage of this
 * functionality should extend OOObject, otherwise the prototype chain will be
 * lacking the above methods at some point, breaking the model.  This is the
 * one important thing that must be done to model your javascript code in this
 * OO style.  This decision was made instead of modifying the primary
 * javascript Object prototype, because the latter decision negatively impacts
 * behaviors with many plugins like jQuery by adding several loopable methods
 * to every object, even single-use object literals, which are frequently used
 * as constructor function arguments.
 * 
 * 
 * Another thing that is gained by having all objects extend OOObject is the
 * internal "settings" property that is common among all instance of OOObject.
 * This provides a consistent means of passing an associative array of settings
 * as a constructor argument to any Constructor Functions, and accessing them
 * in a consistent place (this.settings).  OOObject also provides the option()
 * method, which serves as an all-purpose getter/setter to this.settings.  If
 * you call option() with one parameter, it will return the value.  If you use
 * two parameters, you will set the value.
 * 
 * 
 * Sample Usage:
 * 
 *         Creating an Interface:
 *            //
 *             // All interface functions should remain empty, they are only to
 *             // indicate the contract that classes using them must implement.
 *            // Interface methods must be added to the Interface's prototype.
 *            //
 *             // NOTE:
 *            // One interesting thing here is that the functions appear to be
 *            // named twice.  For example, they follow the format:
 *            //     Interface.functionName = function functionName() {};
 *            // It would be perfectly valid to omit the second occurrence so
 *            // that the lines looked like this:
 *            //     Interface.functionName = function() {};
 *            // However, the reason we put the name in there a second time is
 *            // that it will cause Javascript to populate the Function.name
 *            // property of the resulting function, which is useful to have.
 *            // For example, this module will use it in error messages.
 *            //
 *             function MyInterface() {};
 *             MyInterface.prototype.method1 = function method1() {};
 *             MyInterface.prototype.method2 = function method2() {};
 * 
 * 
 *         Declaring that your class implements an interface:
 *            function MyClass() {};
 *            MyClass.prototype.method1 = function() {
 *                console.log('method 1');
 *            }
 *            MyClass.prototype.method2 = function() {
 *                console.log('method2');
 *            };
 *            MyClass.implement(MyInterface);
 *            //
 *            // Note that you can also use implements if your constructor
 *            // function builds its methods locally rather than in its
 *            // prototype.  Both methods will work with implements().
 *            //
 *            function MyClass() {
 *                this.update = function() {
 *                    console.log('update');
 *                };
 *                this.sendUpdate = function() {
 *                    console.log('sendUpdate');
 *                };
 *            };
 *            MyClass.implement(MyInterface);
 * 
 * 
 *         Declaring that your class implements multiple interfaces:
 *             MyClass.implement([MyInterface, MyInterface2]);
 * 
 * 
 *         Inheritance (basic):
 *            function MySubClass() {
 *            };
 *            MySubClass.prototype.subMethod = function() {
 *                console.log("subMethod");
 *            };
 *            MySubClass.inherits(MyClass);
 *
 *         
 *         Inheritance with calling the parent constructor
 *            function MySubClass(arguments) {
 *                this.inherits(MyClass, arguments);        // call the parent constructor
 *            };
 *            MySubClass.prototype.subMethod = function() {
 *                console.log("subMethod");
 *            };
 *            MySubClass.inherits(MyClass);
 * 
 * 
 *         Inheritance with additional interfaces:
 *             //
 *             // Note, the order here is very important.  Things must be done in
 *             // this order of precedence:
 *             //
 *             // 1.  Inherit from superclass
 *             // 2.  Add methods to the prototype (this must be done after #1
 *             //     because #1 sets the object's prototype, so if you had
 *             //     already added things to the prototype, they would be lost.)
 *             // 3.  Define interface implementations (this must be done last,
 *             //     after the entire prototype is configured, unless all
 *             //     necessary methods are created inside the constructor
 *             //     function)
 *             //
 *            MySubClass.inherits(MyClass);
 *            MySubClass.prototype.remove = function() {};
 *            MySubClass.implement(interfaces.deleteable);
 *
 *
 *        Testing for class identity:
 *            var myobj = new MyClass();
 *            console.log(myobj.isInstanceOf(MyClass));          // true
 *            console.log(myobj.isInstanceOf(MySubClass));       // false
 *            console.log(myobj.isInstanceOf(OtherClass));       // false
 *            var mysubjobj = new MySubClass();
 *            console.log(mysubobj.isInstanceOf(MyClass));       // true
 *            console.log(mysubobj.isInstanceOf(MySubClass));    // true
 *            console.log(mysubobj.isInstanceOf(OtherClass));    // false
 * 
 * 
 *         Testing for interface implementation:
 *            var myobj = new MyClass();
 *            if (myobj.isInstanceOf(MyInterface)) {
 *                console.log('myobj implements MyInterface!');
 *            }
 * 
 * 
 *         Using option() to get and set settings in an OOObject instance.
 *             function MyClass() {};                    // declare a new class
 *             MyClass.inherits(OOObject);               // descend from OOObject
 *             
 *             var myObj = new MyClass();                // new instance
 *             console.log(myObj.option('someVal'));     // null
 *             console.log(myObj.settings.someVal);      // undefined
 *             myObj.option('someVal', 123);        
 *             console.log(myObj.option('someVal'));     // 123
 *             console.log(myObj.settings.someVal);      // 123
 *         
 */




/*****************************************************************************
 * 
 * Modifying Function.prototype
 * 
 *****************************************************************************/


/**
 * Return the name of the current function
 * 
 * This method is required because IE is a ball-sucking jackhole of a browser.
 * 
 * @returns String the name of the function if able to be determined, otherwise
 *                 null
 * @access public
 */
Function.prototype._functionName = function() {
    var name = null, parts;
    
    if (typeof this.name === 'undefined') {
        // I hate you, IE
        parts = this.constructor.toString().split("(")[0].split(/function\s*/);
        if (parts.length > 0) {
            name = parts[parts.length-1];
        }
    } else {
        name = this.name;
    }
    if (name === '') {
        name = 'Anonymous';
    }
    
    return name;
};


/**
 * inherits
 * 
 * This function defines the inheritance between two Constructor Functions.
 * 
 * To use:
 * <code>
 * function Parent() {}
 * Parent.inherits(OOObject);            // required for all "root" objects
 * function Child() {}
 * Child.inherits(Parent);               // Child is both a Parent and an OOObject
 * 
 * // Note:  If you want to call the parent constructor, you will need to use
 * // the OOObject version of inherits() during child constructor
 * // initialization in addition to after the Function is defined:
 * 
 * function Parent() {}
 * Parent.inherits(OOObject);
 * function Child(params) {
 *     this.inherits(Parent, params);    // OOObject version, runs the parent
 *                                       // constructor in the scope of the child
 * }
 * Child.inherits(Parent);               // declares that the Child Constructor
 *                                       // Function inherits from the Parent
 *                                       // Constructor Function
 * </code>
 * 
 * @param Function parent the parent Function from which this Function descends
 * 
 */
Function.prototype.inherits = (function() {
    var F = function() {};                // create a single instance of a proxy function we can reuse each time inherits() is called.  The proxy function protects the parent class' prototype members from being modified later.
    return function(P) {
        var C = this;
        F.prototype = P.prototype;        // set the proxy function's prototype to match the parent's -- NOTE THAT THIS WILL NOT INHERIT PARENT MEMBERS CREATED WITH 'THIS.', IT WILL ONLY INHERIT THINGS IN THE PARENT'S PROTOTYPE
        C.prototype = new F();            // set the child function's prototype to a new instance of the proxy function
        C.superclass = P.prototype;       // provide a handy "superclass" property so we can walk up the chain of parent classes
        C.prototype.constructor = C;      // reset the child function's constructor property so the child doesn't report the parent class as its constructor function
    };
}());


/**
 * implements
 * 
 * Declares that the specified Constructor Function implements the given
 * Interface(s).  In order to make this verification, an instance of the
 * Function must be created with "new", and the instance must be examined to
 * see if all methods defined by the Interface are implemented.  Because of
 * this, it is important that the Constructor function can initialize a new
 * object without any required constructor arguments.  It is also important
 * that the Function is a descendent of OOObject so that the implements()
 * method will be available.
 * 
 * This Function version of implements() is like declaring "This class must
 * implement this interface" as opposed to the OOObject version, which is like
 * asking "Does this object instance implement this interface?"  As such, this
 * version of implements() will throw errors if the Interface is not properly
 * implemented rather than returning a Boolean.
 * 
 * @param mixed interfaces the Interface or Array of Interfaces against which
 *                         to check implementation.
 * 
 * @throws Exception if the specified function does not implement all methods
 *                   declared by all Interfaces.
 * 
 */
Function.prototype.implement = function(interfaces) {
    var obj;
    
    try {
        obj = new this();                            // create a new instance of the Function
    } catch (e) {
        throw new Error('Error', 'Function "' + this._functionName() + '" could not be instantiated during call to implements().  Make sure it is a proper Constructor Function without any required constructor arguments.\n' + e);
    }
    if (obj) {
        if (typeof obj.implement !== 'function') {
            throw new Error('Error', 'Function "' + this._functionName() + '" does not extend OOObject and therefore cannot use the implements() method.\n');
        } else {
            obj.implement(interfaces, true);        // call the OOObject version of implements() in order to check that the instantiated object implements all interface methods
        }
    }
};


/**
 * isInterface
 * 
 * Checks that the specified Constructor Function will produce objects that
 * descend from the base Interface class.  In order to make this verification,
 * an instance of the Function must be created with "new", and the instance
 * must be examined to see if its descendent hierarchy eventually includes
 * Interface.  Because of this, it is important that the Constructor function
 * can initialize a new object without any required constructor arguments.
 * It is also important that the Function is a descendent of OOObject so that
 * the implements() method will be available.
 * 
 * @returns Boolean whether or not the Constructor Function in question
 *                  is an Interface.
 *
 */
Function.prototype.isInterface = function() {
    /*
     * For the Function version of isInterface(), we have to treat the Function
     * as a Constructor and make a new object instance so we can test the
     * inheritance chain of the object for the existance of the Interface
     * Constructor Function.
     */
    var obj;
    
    try {
        obj = new this();                            // create a new instance of the Function
    } catch (e) {
        throw new Error('Error', 'Interface "' + this._functionName() + '" could not be instantiated during call to isInterface().  Make sure it is a proper Constructor Function without any required constructor arguments.\n' + e);
    }
    if (obj) {
        if (typeof obj.isInterface !== 'function') {
            throw new Error('Error', 'Interface "' + this._functionName() + '" does not extend OOObject and therefore cannot be used in the isInterface() method.\n');
        } else {
            return obj.isInterface();                // call the OOObject version of isInterface() so we can check the ancestry to see if we've descended from Interface
        }
    }
    return false;
};




/*****************************************************************************
 * 
 * Defining OOObject (the OO portion)
 * 
 *****************************************************************************/


/**
 * OOObject
 * 
 * This is the root Object for use with the OO package.  This method is used
 * rather than extending Javascript's root Object class, because doing so has
 * other far-reaching ramifications.  For example, even objects declared with
 * object literal syntax would contain the new prototype methods.  Additionally
 * certain frameworks (like jQuery) will frequently loop over objects and
 * execute each method without checking hasOwnProperty().  This happens during
 * $.ajax() against the data option, for example.  This produces unnecessary
 * overhead in processing power and data transmission during AJAX requests,
 * since so many additional methods are unnecessarily executed.
 * 
 * The OOObject will create a local settings property that it will extend with
 * the optional "options" parameter.  In this way, classes that descend from
 * OOObject can simply call their parent constructor during initialization to
 * have a uniform technique for setting up a local settings object that can be
 * overwritten with options from the constructor argument.
 * 
 */
var OOObject = (function($) {
    return function OOObject(options) {
        var self = this;
        
        if (!this.settings) {
            this.settings = {};
        }
        if (!options) {
            options = {};
        }
        
        if ($ != null) {
            this.settings = $.extend(true, {}, this.settings, options);
        }
    };
})(jQuery || null);


/**
 * inherits
 * 
 * This method is a conveience method allowing an Object to run its parent's
 * Constructor Function within the scope of the child object.  This should be
 * run as the first step in initializing a child object inside the Constructor
 * Function:
 * <code>
 * function Child(params) {
 *     this.inherits(Parent, params);    // run Parent constructor in the scope
 *                                       // of the child instance
 * }
 * </code>
 * 
 * It is important that the Child constructor function must be a descendent of
 * OOObject, or this method won't be available.
 * 
 * @param Function parent the parent Function whose constructor should be run
 * 
 */
OOObject.prototype.inherits = function(Parent) {
    // this is the convenience method for calling the parent class's constructor within the scope of the child object
    if (typeof Parent === 'function') {
        if(arguments.length > 1) {
            Parent.apply(this, Array.prototype.slice.call(arguments, 1));
        } else {
            Parent.call(this);
        }
    }
};


/**
 * Return the name of the specified function
 * 
 * This method is required because IE is a ball-sucking jackhole of a browser.
 * 
 * @param Function fn the function whose name we wish to determine
 * 
 * @returns String the name of the function if able to be determined, otherwise
 *                 null
 * @access public
 */
OOObject.prototype._functionName = function(fn) {
    var name = null, parts;
    
    if (typeof fn.name === 'undefined') {
        // I hate you, IE
        parts = this.constructor.toString().split("(")[0].split(/function\s*/);
        if (parts.length > 0) {
            name = parts[parts.length-1];
        }
    } else {
        name = fn.name;
    }
    if (name === '') {
        name = 'Anonymous';
    }
    
    return name;
};


/**
 * implements
 * 
 * This method checks that a given instance of an object implements the
 * Interface or Interfaces supplied.  If an array of interfaces is supplied,
 * all methods of all interfaces must be implemented by the object in order for
 * the method to return true.  If a single parameter is passed, the object must
 * implement all of that Interface's methods.  If something is passed that is
 * not an actual Interface, it is ignored.
 * 
 * This Object version of implements is like asking "Does this object instance
 * implement this interface?" as opposed to the Function version, which is like
 * declaring "This class must implement this interface."  As such, this version
 * of implements() will simply return true/false rather than throwing errors.
 * 
 * @param mixed   interfaces   the Interface or Array of Interfaces against
 *                             which to check implementation
 * @param Boolean throwOnError used internally, indicates whether to return
 *                             booleans or throw on errors
 * 
 * @returns Boolean whether or not the Object instance in question implements
 *                  all of the indicated Interface methods.
 *
 */
OOObject.prototype.implement = function(interfaces, throwOnError) {
    var constructorName,
        _this = this;
    
    constructorName = this._functionName(this.constructor);

    // This is an internal-only function that will loop over an array of
    // Interfaces and run the other iternal function, _implements() against
    // each one
    function _implementsArray(pseudoInterface) {
        var item, okSoFar = true;
        if (interfaces instanceof Array) {
            for (item in interfaces.reverse()) {
                if (typeof interfaces[item] === 'function') {
                    okSoFar = okSoFar && this._implements(interfaces[item]);
                } else {
                    if (throwOnError) {
                        throw new Error('Error', 'The Array parameter "interfaces" contains an interface item that is not a Function');
                    } else {
                        return false;
                    }
                }
            }
        }
        return okSoFar;
    }
    
    // This is an internal-only function that will take a single Interface and
    // validate that the current object has implemented every method that the
    // Interface has implemented.
    function _implements(pseudoInterface) {
        var prop;
        if (typeof pseudoInterface === 'function') {
            if (!pseudoInterface.isInterface()) {
                if (throwOnError) {
                    throw new Error('Error', '"' + constructorName + '" cannot implement "' + pseudoInterface.name + '" because "' + pseudoInterface.name + '" is not an Interface.');
                } else {
                    return false;
                }
            } else {
                for (prop in pseudoInterface.prototype) {
                    if (typeof pseudoInterface.prototype[prop] === 'function') {
                        if (typeof _this[prop] !== 'function') {
                            if (throwOnError) {
                                throw new Error('Error', '"' + constructorName + '" does not implement function "' + prop + '" as contracted in psuedo-interface "' + pseudoInterface.name + '"');
                            } else {
                                return false;
                            }
                        }
                    }
                }
            }
        }
        return true;
    }
    
    // This is the actual code of the method, which finds out which internal
    // function to call, if any
    if (interfaces) {
        try {
            if (interfaces instanceof Array) {
                return _implementsArray(interfaces);
            } else if (typeof interfaces === 'function') {
                return _implements(interfaces);
            } else {
                if (throwOnError) {
                    throw new Error('Error', 'The parameter "interfaces" to method "implements" was not an Array or Function');
                }
            }
        }
        catch (e) {
            if (throwOnError) {
                throw e;        // we're just going to re-throw e, we don't want to squelch the error
            }
        }
    }
    return false;
};


/**
 * isInterface
 * 
 * Checks that the current object instance is actually that of an Interface.
 * This is done by recursively climbing the superclass hierarchy until we reach
 * the end or until Interface is encountered, in which case the object is
 * considered to be an Interface.
 * 
 * @returns Boolean whether or not the current Object instance descends from
 *                  the root Interface object.
 *
 */
OOObject.prototype.isInterface = function() {
    var constructorName = this._functionName(this.constructor);

    if (this.constructor) {
        if (constructorName === 'Interface') {
            // "this" is an object whose constructor's name is Interface
            return true;
        } else if (this.constructor.superclass && this.constructor.superclass.constructor) {
            // "this" wasn't constructed by Interface, but it has a parent class, so we'll recurse up the parent chain
            return new this.constructor.superclass.constructor().isInterface();
        }
    }
    return false;
};


/**
 * isInstanceOf
 * 
 * This method is similar to the built-in Javascript instanceof    operator,
 * except that it will also check against interfaces so that any function or
 * object that implements an interface is considered to be an "instance of"
 * that interface.  This also works correctly with inherited constructor
 * functions/classes.
 * 
 * @param mixed interface the Constructor Function or Interface to check if
 *                        this object extends from or implements
 * 
 * @returns Boolean whether or not this Object instance is a instance of or
 *                  descendent of the given Constructor Function, or whether
 *                  it implements the Interface or a descendent of the given
 *                  Interface.
 *
 */
OOObject.prototype.isInstanceOf = function(pseudoInterface) {
    if (pseudoInterface) {
        if (this instanceof pseudoInterface) {
            return true;
        } else if (typeof pseudoInterface.isInterface === 'function') {
            try {
                if (pseudoInterface.isInterface()) {
                    return this.implement(pseudoInterface);
                }
            } catch (e) {
                // need to catch Function.isInterface's check for instantiated object extending from OOObject
                return false;
            }
        }
    }
    return false;
};




/*****************************************************************************
 * 
 * Extending OOObject (additional, non-OO functionality)
 * 
 *****************************************************************************/


/**
 * option
 * 
 * This method operates on the internal settings object that exists for all
 * OOObjects.  It can take one or two parameters.  If one parameter, it will
 * simply return the current value of that option.  If two values, it will set
 * the value of the option to the provided value and return it.
 * 
 * @param String option the option we're interested in in the object's internal
 *                      settings object.
 * @param mixed  value  (Optional) the value to use to set the option
 * 
 * @returns mixed the value associated with the given option, or null if not
 *                foudn
 * 
 */
OOObject.prototype.option = function() {
    var option = arguments.length > 0 ? arguments[0] : null,
        value = arguments.length > 1 ? arguments[1] : null;
    if (typeof option === 'string' && option !== '') {
        if (arguments.length > 1) {        // we check for the argument length instead of 'value' being defined because they might be intentionally setting a null value
            this.settings[option] = value;
            return value;
        } else {
            if (this.settings.hasOwnProperty(option)) {
                return this.settings[option];
            }
        }
    }
    return null;
};





/*****************************************************************************
 * 
 * Interface Setup
 * 
 *****************************************************************************/


/**
 * Declare the base Interface abstract class that will identify each interface
 * as a real interface.
 */
function Interface() {};
Interface.inherits(OOObject);

