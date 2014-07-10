<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @package Aura.Session
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Session;

/**
 *
 * A session segment; lazy-loads from the session.
 *
 * @package Aura.Session
 *
 */
class Segment implements SegmentInterface
{
    /**
     *
     * The session manager.
     *
     * @var Session
     *
     */
    protected $session;

    /**
     *
     * The segment name.
     *
     * @var string
     *
     */
    protected $name;

    /**
     *
     * The data in the segment is a reference to a $_SESSION key.
     *
     * @var array
     *
     */
    protected $data;

    /**
     *
     * Flash values for this request; a reference to a $_SESSION key.
     *
     * @var array
     *
     */
    protected $flash_now;

    /**
     *
     * Flash values for the next request; a reference to a $_SESSION key.
     *
     * @var array
     *
     */
    protected $flash_next;

    /**
     *
     * Constructor.
     *
     * @param Session $session The session manager.
     *
     * @param string $name The segment name.
     *
     */
    public function __construct(Session $session, $name)
    {
        $this->session = $session;
        $this->name = $name;
    }

    /**
     *
     * Returns the value of a key in the segment.
     *
     * @param string $key The key in the segment.
     *
     * @return mixed
     *
     */
    public function get($key, $alt = null)
    {
        $this->resumeSession();
        return isset($this->data[$key])
             ? $this->data[$key]
             : $alt;
    }

    /**
     *
     * Sets the value of a key in the segment.
     *
     * @param string $key The key to set.
     *
     * @param mixed $val The value to set it to.
     *
     */
    public function set($key, $val)
    {
        $this->resumeOrStartSession();
        $this->data[$key] = $val;
    }

    /**
     *
     * Clear all data from the segment.
     *
     * @return null
     *
     */
    public function clear()
    {
        if ($this->resumeSession()) {
            $this->data = array();
        }
    }

    /**
     *
     * Sets a flash value for the *next* request.
     *
     * @param string $key The key for the flash value.
     *
     * @param mixed $val The flash value itself.
     *
     */
    public function setFlash($key, $val)
    {
        $this->resumeOrStartSession();
        $this->flash_next[$key] = $val;
    }

    /**
     *
     * Gets the flash value for a key in the *current* request.
     *
     * @param string $key The key for the flash value.
     *
     * @return mixed The flash value itself.
     *
     */
    public function getFlash($key, $alt = null)
    {
        $this->resumeSession();
        return isset($this->flash_now[$key])
             ? $this->flash_now[$key]
             : $alt;
    }

    /**
     *
     * Clears flash values for *only* the next request.
     *
     * @return null
     *
     */
    public function clearFlash()
    {
        if ($this->resumeSession()) {
            $this->flash_next = array();
        }
    }

    /**
     *
     * Gets the flash value for a key in the *next* request.
     *
     * @param string $key The key for the flash value.
     *
     * @return mixed The flash value itself.
     *
     */
    public function getFlashNext($key, $alt = null)
    {
        $this->resumeSession();
        return isset($this->flash_next[$key])
             ? $this->flash_next[$key]
             : $alt;
    }

    /**
     *
     * Sets a flash value for the *next* request *and* the current one.
     *
     * @param string $key The key for the flash value.
     *
     * @param mixed $val The flash value itself.
     *
     */
    public function setFlashNow($key, $val)
    {
        $this->resumeOrStartSession();
        $this->flash_now[$key] = $val;
        $this->flash_next[$key] = $val;
    }

    /**
     *
     * Clears flash values for *both* the next request *and* the current one.
     *
     * @return null
     *
     */
    public function clearFlashNow()
    {
        if ($this->resumeSession()) {
            $this->flash_now = array();
            $this->flash_next = array();
        }
    }

    /**
     *
     * Retains all the current flash values for the next request; values that
     * already exist for the next request take precedence.
     *
     * @return null
     *
     */
    public function keepFlash()
    {
        if ($this->resumeSession()) {
            $this->flash_next = array_merge(
                $this->flash_next,
                $this->flash_now
            );
        }
    }

    /**
     *
     * Has the segment been loaded with session values?
     *
     * @return bool
     *
     */
    protected function isLoaded()
    {
        return $this->data !== null;
    }

    /**
     *
     * Loads the segment only if the session has already been started, or if
     * a session is available (in which case it resumes the session first).
     *
     * @return bool
     *
     */
    protected function resumeSession()
    {
        if ($this->isLoaded()) {
            return true;
        }

        if ($this->session->isStarted() || $this->session->resume()) {
            $this->load();
            return true;
        }

        return false;
    }

    /**
     *
     * Sets the segment properties to $_SESSION references.
     *
     * @return null
     *
     */
    protected function load()
    {
        if (! isset($_SESSION[$this->name])) {
            $_SESSION[$this->name] = array();
        }

        if (! isset($_SESSION['Aura\Session']['flash_now'][$this->name])) {
            $_SESSION['Aura\Session']['flash_now'][$this->name] = array();
        }

        if (! isset($_SESSION['Aura\Session']['flash_next'][$this->name])) {
            $_SESSION['Aura\Session']['flash_next'][$this->name] = array();
        }

        $this->data       =& $_SESSION[$this->name];
        $this->flash_now  =& $_SESSION['Aura\Session']['flash_now'][$this->name];
        $this->flash_next =& $_SESSION['Aura\Session']['flash_next'][$this->name];
    }

    /**
     *
     * Resumes a previous session, or starts a new one, and loads the segment.
     *
     * @return null
     *
     */
    protected function resumeOrStartSession()
    {
        if (! $this->resumeSession()) {
            $this->session->start();
            $this->load();
        }
    }
}
