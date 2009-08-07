<?php
/*
    Copyright 2009 Rob Apodaca <rob.apodaca@gmail.com>

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
abstract class Dispatcher
{
    /**
     * The suffix used to append to the class name
     * @var string
     * @static
     */
    private static $_suffix = '.php';

    /**
     * The path to look for classes (or controllers)
     * @var string
     * @static
     */
    private static $_classPath;

    /**
     * Attempts to dispatch the supplied Route object. Returns false if fails
     * @param Route $route
     * @throws classFileNotFoundException
     * @throws badClassNameException
     * @throws classNameNotFoundException
     * @throws classMethodNotFoundException
     * @throws classNotSpecifiedException
     * @throws methodNotSpecifiedException
     * @return boolean
     * @access public
     * @static
     */
    public static function dispatch( Route &$route )
    {
        $class      = $route->getMapClass();
        $method     = $route->getMapMethod();
        $arguments  = $route->getMapArguments();

        if( '' === trim($class) )
        {
            throw new classNotSpecifiedException('Class Name not specified');
            return FALSE;
        }

        if( '' === trim($method) )
        {
            throw new methodNotSpecifiedException('Method Name not specified');
            return FALSE;
        }

        //Because the class could have been matched as a dynamic element,
        // it would mean that the value in $class is untrusted. Therefore,
        // it may only contain alphanumeric characters. Anything not matching
        // the regexp is considered potentially harmful.
        $class = str_replace('\\', '', $class);
        preg_match('/^[a-zA-Z0-9_]+$/', $class, $matches);
        if( count($matches) !== 1 )
        {
            throw new badClassNameException('Disallowed characters in class name ' . $class);
            return FALSE;
        }

        //Apply the suffix
        $file_name = self::$_classPath . $class . self::$_suffix;
        $class = $class . str_replace('.php', '', self::$_suffix);
        
        //At this point, we are relatively assured that the file name is safe
        // to check for it's existence and require in.
        if( FALSE === file_exists($file_name) )
        {
            throw new classFileNotFoundException('Class file not found');
            return FALSE;
        }
        else
        {
            require_once($file_name);
        }

        //Check for the class class
        if( FALSE === class_exists($class) )
        {
            throw new classNameNotFoundException('class not found ' . $class);
            return FALSE;
        }

        //Check for the method
        if( FALSE === method_exists($class, $method))
        {
            throw new classMethodNotFoundException('method not found ' . $method);
            return FALSE;
        }

        //All above checks should have confirmed that the class can be instatiated
        // and the method can be called
        $obj = new $class;
        $call_func_result = call_user_func(array($obj, $method), $arguments);

        //PHP's call_user_func array returns false if there was an error
        if( FALSE === $call_func_result )
            return FALSE;
        else
            return TRUE;
    }

    /**
     * Sets a suffix to append to the class name being dispatched
     * @param string $suffix
     * @access public
     * @return void
     */
    public function setSuffix( $suffix )
    {
        self::$_suffix = $suffix . '.php';
    }

    /**
     * Set the path where dispatch class (controllers) reside
     * @param string $path
     * @access public
     * @return void
     */
    public function setClassPath( $path )
    {
        $path = preg_replace('/\/$/', '', $path);

        self::$_classPath = $path . '/';
    }
}

class badClassNameException extends Exception{}
class classFileNotFoundException extends Exception{}
class classNameNotFoundException extends Exception{}
class classMethodNotFoundException extends Exception{}
class classNotSpecifiedException extends Exception{}
class methodNotSpecifiedException extends Exception{}

?>
