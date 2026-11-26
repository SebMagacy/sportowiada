<?php

namespace App\Filament\Pages;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Support\Components\Attributes\Exposed;
use Illuminate\Auth\Events\PasswordResetLinkSent;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Support\Facades\Password;
use LogicException;
use SensitiveParameter;

class RequestPasswordReset extends BaseRequestPasswordReset
{
    #[Exposed]
    public function request(): void
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return;
        }

        $data = $this->form->getState();

        Password::broker(Filament::getAuthPasswordBroker())->sendResetLink(
            $this->getCredentialsFromFormData($data),
            function (CanResetPassword $user, #[SensitiveParameter] string $token): void {
                if (! method_exists($user, 'notify')) {
                    $userClass = $user::class;

                    throw new LogicException("Model [{$userClass}] does not have a [notify()] method.");
                }

                $notification = app(ResetPasswordNotification::class, ['token' => $token]);
                $notification->url = Filament::getResetPasswordUrl($token, $user);

                $user->notify($notification);

                event(new PasswordResetLinkSent($user));
            },
        );

        Notification::make()
            ->title(__('passwords.request_received'))
            ->success()
            ->send();

        $this->form->fill();
    }
}
