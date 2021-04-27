<?php
	
	declare(strict_types=1);
	
	namespace Digitell\LiveEventsPlatformAPI\Sessions\BrowserAuth;
	
	use Firebase\JWT\JWT;
	
	final class IdentitySessionEntryBuilder
	{
		/** @var int - Can view the stream but cannot interact with chat and questions */
		public const ACCESS_LEVEL_OBSERVER = 10;
		
		/** @var int - Can view the stream and partake of all interactive features */
		public const ACCESS_LEVEL_PARTICIPANT = 50;
		
		/** @var int - As with participant, but access to host functions such as moderation */
		public const ACCESS_LEVEL_HOST = 300;
		
		/** @var int - As with host but can access in-built presentation tools for the slide deck */
		public const ACCESS_LEVEL_PRESENTER = 700;
		
		/** @var string */
		private $provider_name;
		
		/** @var string */
		private $provider_cert;
		
		/** @var string */
		private $domain;
		
		/** @var string */
		private $name = '';
		
		/** @var string */
		private $email = '';
		
		/** @var string */
		private $session_reference = '';
		
		/** @var string */
		private $identifier = '';
		
		/** @var int - Which access to grant the user */
		private $access_level = 0;
		
		/** @var string - Custom title to grant the user */
		private $custom_title = '';
		
		/** @var bool - Should popups be offered */
		private $offer_popup = false;
		
		/** @var int - When should the token expire at */
		private $expiry_time = 3600 * 24 * 14;
		
		/** @var int - iat value, used for debugging */
		private $issued_time = 0;
		
		/** @var string - The jti unique id */
		private $nonce = '';
		
		/** @var bool - Should we skip the system checker */
		private $show_system_checker = true;
		
		/** @var array - Extra psf parameters */
		private $extra_url_parameters = [];
		
		/**
		 * IdentityRegistrationEntry constructor.
		 *
		 * @param string $provider_name - Certificate name as provided to you by Digitell, Inc.
		 * @param string $provider_cert - Contains the PEM string as read from either a file or database
		 * @param string $domain        - Default domain to send users to
		 *
		 * @throws \Exception - If there is an exception generating enough randomness
		 */
		
		public function __construct(string $provider_name, string $provider_cert, string $domain = 'https://live2.digitell.io') {
			$this->provider_name = $provider_name;
			$this->provider_cert = $provider_cert;
			$this->domain        = $domain;
			$this->issued_time   = time();
			$this->expiry_time   = time() + 300;
			$this->nonce         = bin2hex(random_bytes(16));
		}
		
		/**
		 * Creates the URL containing parameters and the token
		 *
		 * This will build a URL based on the class properties. In almost all circumstances, you should only call
		 * this function ONCE per instance of the class, as certain properties, such as the nonce, are created
		 * when the class is initialized.
		 *
		 * @return string
		 */
		
		public function toUrl(): string {
			/* these are the parameters which are signed */
			$payload = [
				'aud'      => [
					'live.digitellinc.com/api/browser/authorize/sl/identity',
					parse_url($this->domain, PHP_URL_HOST),
					$this->domain . '/api/browser/authorize/sl/identity',
				],
				'identity' => [
					'identifier' => $this->identifier,
					'name'       => $this->name,
					'email'      => $this->email,
				],
				'session'  => $this->session_reference,
				'iat'      => $this->issued_time,
				'exp'      => $this->expiry_time,
				'jti'      => $this->nonce,
			];
			
			if ($this->access_level) {
				$payload['login']['access'] = $this->access_level;
			}
			
			if ($this->custom_title !== '') {
				$payload['login']['custom_title'] = $this->custom_title;
			}
			
			$query = [
				'token' => JWT::encode($payload, $this->provider_cert, 'RS256', $this->provider_name),
			];
			
			if ($this->isOfferingPopup()) {
				$query['psf_offer_popup'] = '1';
			}
			
			if ($this->isShowingSystemChecker() === false) {
				$query['psf_syschecker'] = '0';
			}
			
			return $this->domain . '/api/browser/authorize/sl/identity?' . \http_build_query($query);
		}
		
		/**
		 * @return bool
		 */
		public function isOfferingPopup(): bool {
			return $this->offer_popup;
		}
		
		/**
		 * @return bool
		 */
		public function isShowingSystemChecker(): bool {
			return $this->show_system_checker;
		}
		
		/**
		 * Sets if the user should be offered a popup option
		 *
		 * The live events platform works best when launched in a popup, this provides the maximum
		 * amount of screen real-estate for media and interactive features. You should usually ensure
		 * that anyone launching a player through this API starts in a popup window, however if that
		 * is not possible, setting $offer_popup to true will give the user that choice when they first
		 * hit the website.
		 *
		 * @param bool $offer_popup
		 * @return $this
		 */
		public function setOfferPopup(bool $offer_popup) {
			$this->offer_popup = $offer_popup;
			return $this;
		}
		
		/**
		 * Sets the relative timestamp when the authentication token will expire
		 *
		 * This function is similar to setExpiryTime except it will add the $seconds
		 * on to the current time.
		 *
		 * @param int $seconds
		 * @return $this
		 */
		
		public function setExpiryIn(int $seconds) {
			$this->setExpiryTime(time() + $seconds);
			return $this;
		}
		
		/**
		 * Set if you want the user to pass through the system checker
		 *
		 * By default, when a user enters the platform for the first time each day, they will
		 * pass through the system checker which will check for compatibility issues, inform the
		 * user of any problems they might have, and log the information to the platform's database
		 * so that it may be accessed by technical support staff rendering assistance.
		 *
		 * You can disable users passing through the system checker by setting this value to false,
		 * however this is NOT recommended.
		 *
		 * @param bool $show_system_checker
		 * @return $this
		 */
		public function setShowSystemChecker(bool $show_system_checker) {
			$this->show_system_checker = $show_system_checker;
			return $this;
		}
		
		/**
		 * @return string
		 */
		public function getProviderName(): string {
			return $this->provider_name;
		}
		
		/**
		 * @return string
		 */
		public function getProviderCert(): string {
			return $this->provider_cert;
		}
		
		/**
		 * @return string
		 */
		public function getDomain(): string {
			return $this->domain;
		}
		
		/**
		 * @return int
		 */
		public function getExpiryTime(): int {
			return $this->expiry_time;
		}
		
		/**
		 * Set the absolute timestamp when the authentication token will expire.
		 *
		 * By default, the token will expire 5 minutes after it has been generated, you
		 * may alter this value, however the events platform may chose to reject tokens
		 * that expire too far into the future.
		 *
		 * @param int $expiry_time
		 * @return $this
		 */
		public function setExpiryTime(int $expiry_time) {
			$this->expiry_time = $expiry_time;
			return $this;
		}
		
		/**
		 * @return int
		 */
		public function getIssuedTime(): int {
			return $this->issued_time;
		}
		
		/**
		 * Set when the authentication token was generated.
		 *
		 * Debugging use only; the live event platform may reject tokens which were generated too far
		 * into the past.
		 *
		 * @param int $issued_time
		 * @return $this
		 */
		public function setIssuedTime(int $issued_time) {
			$this->issued_time = $issued_time;
			return $this;
		}
		
		/**
		 * @return string
		 */
		public function getNonce(): string {
			return $this->nonce;
		}
		
		/**
		 * Sets the cryptographic nonce
		 *
		 * Each time a code is generated, it should have a single, unique nonce value provided
		 * to it. This code is used to track individual requests and can be used to reject specific
		 * tokens.
		 *
		 * Nonce values should be completely random strings, if different tokens are given the same
		 * nonce they may be rejected.
		 *
		 * @param string $nonce - Custom nonce to set
		 * @return $this
		 */
		public function setNonce(string $nonce) {
			$this->nonce = $nonce;
			return $this;
		}
		
		/**
		 * @return array
		 */
		public function getExtraUrlParameters(): array {
			return $this->extra_url_parameters;
		}
		
		/**
		 * Sets additional parameters
		 *
		 * There are various keys and hooks which can be passed through to the authentication
		 * script.
		 *
		 * @param array $extra_url_parameters
		 * @return $this
		 */
		public function setExtraUrlParameters(array $extra_url_parameters) {
			$this->extra_url_parameters = $extra_url_parameters;
			return $this;
		}
		
		/**
		 * @return string
		 */
		public function getName(): string {
			return $this->name;
		}
		
		/**
		 * @param string $name
		 * @return $this
		 */
		public function setName(string $name) {
			$this->name = $name;
			return $this;
		}
		
		/**
		 * @return string
		 */
		public function getEmail(): string {
			return $this->email;
		}
		
		/**
		 * @param string $email
		 * @return $this
		 */
		public function setEmail(string $email) {
			$this->email = $email;
			return $this;
		}
		
		/**
		 * @return string
		 */
		public function getSessionReference(): string {
			return $this->session_reference;
		}
		
		/**
		 * @param string $session_reference
		 * @return $this
		 */
		public function setSessionReference(string $session_reference) {
			$this->session_reference = $session_reference;
			return $this;
		}
		
		/**
		 * @return string
		 */
		public function getIdentifier(): string {
			return $this->identifier;
		}
		
		/**
		 * @param string $identifier
		 * @return $this
		 */
		public function setIdentifier(string $identifier) {
			$this->identifier = $identifier;
			return $this;
		}
		
		/**
		 * @return int
		 */
		public function getAccessLevel(): int {
			return $this->access_level;
		}
		
		/**
		 * Sets the desired access level (if different from default).
		 *
		 * Please see the ACCESS_LEVEL_XX constants of this class
		 *
		 * @param int $access_level
		 * @return $this
		 */
		public function setAccessLevel(int $access_level) {
			$this->access_level = $access_level;
			return $this;
		}
		
		/**
		 * @return string
		 */
		public function getCustomTitle(): string {
			return $this->custom_title;
		}
		
		/**
		 * @param string $custom_title
		 * @return $this
		 */
		public function setCustomTitle(string $custom_title) {
			$this->custom_title = $custom_title;
			return $this;
		}
	}