<?php

namespace App\Http\Services;

use App\ActivationRepository;
use App\User;
use Illuminate\Mail\Mailer;

class ActivationService {

	protected $mailer;

	protected $activationRepo;

	protected $resendAfter = 24;

	public function __construct(Mailer $mailer, ActivationRepository $activationRepo) {
		$this->mailer = $mailer;
		$this->activationRepo = $activationRepo;
	}

	public function sendActivationMail($user) {

		if ($user->activated || !$this->shouldSend($user)) {
			return;
		}

		$token = $this->activationRepo->createActivation($user);

		$this->mailer->send('auth.emails.verify', ['token' => $token], function ($message) use ($user) {
			$message->to($user->email)->subject('Activation mail');
		});

	}

	public function activateUser($token) {
		$activation = $this->activationRepo->getActivationByToken($token);

		if ($activation === null) {
			return null;
		}

		$user = User::find($activation->user_id);

		$user->activated = true;

		$user->save();

		$this->activationRepo->deleteActivation($token);

		return $user;

	}

	private function shouldSend($user) {
		$activation = $this->activationRepo->getActivation($user);
		return $activation === null || strtotime($activation->created_at) + 60 * 60 * $this->resendAfter < time();
	}

}