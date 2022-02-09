<?php

namespace MikeFrancis\LaravelUnleash\Unleash;

use Illuminate\Http\Request;

class Context
{
    protected $userId;
    protected $sessionId;
    protected $ipAddress;
    protected $customProperties = [];
    protected $environment;
    protected $appName;

    public function __construct(Request $request)
    {
        $user = $request->user();
        if ($user !== null) {
            $this->userId = $user->getAuthIdentifier();
        }
        try {
            $session = $request->session();
            if ($session !== null) {
                $this->sessionId = $session->getId();
            }
        } catch (\RuntimeException $e) { }
        $this->ipAddress = $request->getClientIp();
        $this->environment = app()->environment();
        $this->appName = config('app.name');
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    public function getEnvironment(): ?string
    {
        return $this->environment;
    }

    public function getAppName(): ?string
    {
        return $this->appName;
    }

    public function getContextValue($name)
    {
        switch ($name) {
            case 'userId':
                return $this->getUserId();
            case 'ipAddress':
                return $this->getIpAddress();
            case 'sessionId':
                return $this->getSessionId();
            case 'environment':
                return $this->getEnvironment();
            case 'appName':
                return $this->getAppName();
            default:
                if (isset($this->customProperties[$name])) {
                    return $this->customProperties[$name];
                }
        }

        return null;
    }

    public function __isset($name)
    {
        return isset($this->customProperties[$name]);
    }

    public function __get($name)
    {
        return $this->customProperties[$name] ?? null;
    }

    public function __set($name, $value)
    {
        $this->customProperties[$name] = $value;
    }

    public function __unset($name)
    {
        unset($this->customProperties[$name]);
    }
}