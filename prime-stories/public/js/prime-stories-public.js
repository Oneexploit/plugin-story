(function () {
	if (typeof window === "undefined" || typeof document === "undefined") {
		return;
	}

	const config = window.primeStoriesConfig || {};
	const namespace = (window.PrimeStories = window.PrimeStories || {});
	const seenStorageKey = "primeStoriesSeen";
	const seenSlideStorageKey = "primeStoriesSeenSlides";
	const sessionStorageKey = "primeStoriesSessionId";
	const sessionCookieKey = "prime_stories_session_id";
	const reportedIssues = new Set();
	const doNotTrack =
		config.respectDnt &&
		(window.navigator.doNotTrack === "1" || window.navigator.msDoNotTrack === "1" || window.doNotTrack === "1");

	function stringifyError(error) {
		if (!error) {
			return "";
		}

		if (typeof error === "string") {
			return error;
		}

		if (error instanceof Error) {
			return [error.message || "", error.stack || ""].filter(Boolean).join(" | ").slice(0, 800);
		}

		try {
			return JSON.stringify(error).slice(0, 800);
		} catch (stringifyFailure) {
			return String(error).slice(0, 800);
		}
	}

	function reportClientIssue(message, context, level, source) {
		if (!config.enableLogging || !config.enableClientLog || !config.restUrl || typeof window.fetch !== "function") {
			return Promise.resolve(null);
		}

		const payloadContext = context && typeof context === "object" ? context : {};
		let contextFragment = "";
		try {
			contextFragment = JSON.stringify(payloadContext).slice(0, 240);
		} catch (error) {
			contextFragment = "[unserializable-context]";
		}

		const fingerprint = [source || "viewer.client", message || "", contextFragment].join("|");
		if (reportedIssues.has(fingerprint)) {
			return Promise.resolve(null);
		}

		reportedIssues.add(fingerprint);

		let sessionId = "";
		try {
			sessionId = window.localStorage.getItem(sessionStorageKey) || "";
		} catch (storageError) {
			sessionId = "";
		}

		return request(
			"/log",
			{
				level: level || "error",
				source: source || "viewer.client",
				message: message || "Frontend diagnostic event reported.",
				context: Object.assign(
					{
						requestId: config.requestId || "",
						location: window.location ? window.location.href : "",
					},
					payloadContext
				),
				session_id: sessionId,
			},
			{ suppressLogging: true }
		);
	}

	function parseStoredIds() {
		try {
			const raw = window.localStorage.getItem(seenStorageKey);
			const parsed = raw ? JSON.parse(raw) : [];
			return new Set(Array.isArray(parsed) ? parsed.map((value) => Number(value)) : []);
		} catch (error) {
			reportClientIssue(
				"Failed to parse seen story state from localStorage.",
				{ error: stringifyError(error) },
				"warning",
				"viewer.storage.parse_seen"
			);
			return new Set();
		}
	}

	function storeSeenIds(seenIds) {
		try {
			window.localStorage.setItem(seenStorageKey, JSON.stringify(Array.from(seenIds)));
		} catch (error) {
			reportClientIssue(
				"Failed to persist seen story state to localStorage.",
				{ error: stringifyError(error) },
				"warning",
				"viewer.storage.save_seen"
			);
		}
	}

	function parseStoredStrings(key) {
		try {
			const raw = window.localStorage.getItem(key);
			const parsed = raw ? JSON.parse(raw) : [];
			return new Set(Array.isArray(parsed) ? parsed.map((value) => String(value)).filter(Boolean) : []);
		} catch (error) {
			reportClientIssue(
				"Failed to parse story slide state from localStorage.",
				{ error: stringifyError(error), key: key },
				"warning",
				"viewer.storage.parse_slides"
			);
			return new Set();
		}
	}

	function storeStringSet(key, values) {
		try {
			window.localStorage.setItem(key, JSON.stringify(Array.from(values)));
		} catch (error) {
			reportClientIssue(
				"Failed to persist story slide state to localStorage.",
				{ error: stringifyError(error), key: key },
				"warning",
				"viewer.storage.save_slides"
			);
		}
	}

	function getSessionId() {
		try {
			const existing = window.localStorage.getItem(sessionStorageKey);
			if (existing) {
				writeSessionCookie(existing);
				return existing;
			}

			const created =
				typeof crypto !== "undefined" && typeof crypto.randomUUID === "function"
					? crypto.randomUUID()
					: "prime-stories-" + Date.now() + "-" + Math.floor(Math.random() * 100000);

			window.localStorage.setItem(sessionStorageKey, created);
			writeSessionCookie(created);
			return created;
		} catch (error) {
			reportClientIssue(
				"Failed to create a stable frontend session ID.",
				{ error: stringifyError(error) },
				"warning",
				"viewer.storage.session"
			);
			const fallback = "prime-stories-" + Date.now();
			writeSessionCookie(fallback);
			return fallback;
		}
	}

	function writeSessionCookie(sessionId) {
		if (!sessionId || !config.enableGuestSeen || doNotTrack) {
			return;
		}

		const retentionDays = Math.max(1, Number(config.guestSeenDays) || 30);
		const expires = new Date(Date.now() + retentionDays * 86400000).toUTCString();
		document.cookie =
			sessionCookieKey +
			"=" +
			encodeURIComponent(sessionId) +
			"; expires=" +
			expires +
			"; path=/; SameSite=Lax";
	}

	function request(endpoint, payload, options) {
		const requestOptions = options || {};

		if (!config.restUrl || typeof window.fetch !== "function") {
			return Promise.resolve(null);
		}

		return window
			.fetch(config.restUrl + endpoint, {
				method: "POST",
				credentials: "same-origin",
				headers: {
					"Content-Type": "application/json",
					"X-Prime-Stories-Nonce": config.publicNonce || "",
					"X-WP-Nonce": config.restNonce || "",
				},
				body: JSON.stringify(payload || {}),
			})
			.then(function (response) {
				if (!response.ok && !requestOptions.suppressLogging && endpoint !== "/log") {
					reportClientIssue(
						"REST request returned a non-success status.",
						{
							endpoint: endpoint,
							status: response.status,
						},
						"warning",
						"viewer.request"
					);
				}

				return response;
			})
			.catch(function (error) {
				if (!requestOptions.suppressLogging && endpoint !== "/log") {
					reportClientIssue(
						"REST request failed unexpectedly.",
						{
							endpoint: endpoint,
							error: stringifyError(error),
						},
						"warning",
						"viewer.request"
					);
				}

				return null;
			});
	}

	function getLabel(key, fallback) {
		return config.i18n && config.i18n[key] ? config.i18n[key] : fallback;
	}

	function chooseSource(element) {
		const isMobile = window.matchMedia && window.matchMedia("(max-width: 767px)").matches;
		const mobileSource = element.getAttribute("data-mobile-src");
		const desktopSource = element.getAttribute("data-desktop-src");
		return (isMobile && mobileSource) || desktopSource || mobileSource || "";
	}

	class PrimeStoriesInstance {
		constructor(wrapper) {
			if (!wrapper || wrapper.dataset.primeStoriesInitialized === "true") {
				return;
			}

			this.wrapper = wrapper;
			this.instanceId = wrapper.getAttribute("data-instance-id") || "";
			this.track = wrapper.querySelector(".prime-stories-track");
			this.items = Array.from(wrapper.querySelectorAll("[data-story-trigger]"));
			this.viewer = wrapper.querySelector(".prime-stories-viewer");
			this.dialog = wrapper.querySelector(".prime-stories-dialog");
			this.slides = Array.from(wrapper.querySelectorAll("[data-story-slide]"));
			this.progressBars = Array.from(wrapper.querySelectorAll(".prime-stories-progress-bar"));
			this.closeButton = wrapper.querySelector("[data-story-close]");
			this.nextButton = wrapper.querySelector("[data-story-next]");
			this.prevButton = wrapper.querySelector("[data-story-prev]");
			this.muteButton = wrapper.querySelector("[data-story-mute]");
			this.autoplay = wrapper.getAttribute("data-autoplay") !== "false";
			this.activeIndex = -1;
			this.animationFrame = null;
			this.progressDuration = 0;
			this.elapsed = 0;
			this.startedAt = 0;
			this.isPaused = false;
			this.isMuted = true;
			this.wasPlayingVideo = false;
			this.lastFocusedElement = null;
			this.sessionId = getSessionId();
			this.sentImpressions = new Set();
			this.seenIds = parseStoredIds();
			this.seenSlideIds = parseStoredStrings(seenSlideStorageKey);
			this.remoteSeenIds = new Set();
			this.touchStartX = 0;
			this.touchStartY = 0;
			this.keydownHandler = this.handleKeydown.bind(this);
			this.visibilityHandler = this.handleVisibilityChange.bind(this);

			if (!this.items.length || !this.viewer || !this.dialog) {
				return;
			}

			wrapper.dataset.primeStoriesInitialized = "true";
			this.restoreSeenState();
			this.bind();
			this.observeImpressions();

			if (!config.lazyLoadMedia) {
				this.slides.forEach((slide) => this.loadSlideMedia(slide));
			}
		}

		bind() {
			this.items.forEach((item) => {
				item.addEventListener("click", () => {
					this.open(Number(item.getAttribute("data-story-index")));
				});
			});

			if (this.closeButton) {
				this.closeButton.addEventListener("click", () => this.close());
			}

			if (this.nextButton) {
				this.nextButton.addEventListener("click", () => this.next());
			}

			if (this.prevButton) {
				this.prevButton.addEventListener("click", () => this.previous());
			}

			if (this.muteButton) {
				this.muteButton.addEventListener("click", () => this.toggleMute());
			}

			this.dialog.addEventListener("pointerdown", (event) => {
				if (event.target.closest("[data-story-cta], [data-story-action], [data-story-close], [data-story-next], [data-story-prev], [data-story-mute]")) {
					return;
				}

				this.pause();
			});

			["pointerup", "pointercancel", "mouseleave"].forEach((eventName) => {
				this.dialog.addEventListener(eventName, () => this.resume());
			});

			this.dialog.addEventListener(
				"touchstart",
				(event) => {
					const touch = event.changedTouches[0];
					if (!touch) {
						return;
					}

					this.touchStartX = touch.clientX;
					this.touchStartY = touch.clientY;
				},
				{ passive: true }
			);

			this.dialog.addEventListener(
				"touchend",
				(event) => {
					const touch = event.changedTouches[0];
					if (!touch) {
						return;
					}

					const distance = touch.clientX - this.touchStartX;
					const verticalDistance = touch.clientY - this.touchStartY;
					if (verticalDistance > 70 && verticalDistance > Math.abs(distance)) {
						this.close();
						return;
					}

					if (Math.abs(distance) < 30) {
						return;
					}

					if (distance < 0) {
						this.next();
					} else {
						this.previous();
					}
				},
				{ passive: true }
			);

			this.slides.forEach((slide) => {
				const cta = slide.querySelector("[data-story-cta]");
				const media = slide.querySelector("[data-story-clickable]");
				const action = slide.querySelector("[data-story-action]");

				if (cta) {
					cta.addEventListener("click", () => {
						this.trackEvent("click", Number(slide.getAttribute("data-story-id")), this.getSlideMeta(slide));
					});
				}

				if (action) {
					action.addEventListener("click", (event) => {
						const reaction = event.target.closest("[data-story-reaction]");
						if (!reaction) {
							return;
						}

						this.trackEvent("reaction", Number(slide.getAttribute("data-story-id")), {
							reaction: reaction.getAttribute("data-story-reaction") || "",
							slide: slide.getAttribute("data-slide-id") || "",
						});
						action.querySelectorAll("[data-story-reaction]").forEach((button) => {
							button.classList.toggle("is-selected", button === reaction);
						});
					});

					const reply = action.querySelector("[data-story-reply]");
					const replySubmit = action.querySelector("[data-story-reply-submit]");
					const sendReply = () => {
						if (!reply || !reply.value.trim()) {
							return;
						}

						this.trackEvent("reply", Number(slide.getAttribute("data-story-id")), {
							slide: slide.getAttribute("data-slide-id") || "",
							reply: reply.value.trim(),
						});
						reply.value = "";
						reply.setAttribute("placeholder", getLabel("replySent", "Reply sent"));
					};

					if (reply) {
						reply.addEventListener("keydown", (event) => {
							if (event.key !== "Enter" || !reply.value.trim()) {
								return;
							}

							event.preventDefault();
							sendReply();
						});
					}

					if (replySubmit) {
						replySubmit.addEventListener("click", sendReply);
					}
				}

				if (media) {
					media.addEventListener("click", () => {
						const openOnClick = slide.getAttribute("data-open-on-click") === "true";
						const url = slide.getAttribute("data-button-url");
						const target = slide.getAttribute("data-button-target");

						if (!openOnClick || !url) {
							return;
						}

						this.trackEvent("click", Number(slide.getAttribute("data-story-id")), this.getSlideMeta(slide));
						if (target === "new_tab") {
							window.open(url, "_blank", "noopener");
						} else {
							window.location.assign(url);
						}
					});
				}
			});
		}

		restoreSeenState() {
			this.items.forEach((item) => {
				const storyId = Number(item.getAttribute("data-story-id"));
				if (this.seenIds.has(storyId)) {
					item.classList.add("prime-stories-item-seen");
					item.classList.remove("prime-stories-item-unseen");
				}
			});
		}

		observeImpressions() {
			if (!config.enableAnalytics) {
				return;
			}

			if (!("IntersectionObserver" in window)) {
				this.items.forEach((item) => this.trackImpression(Number(item.getAttribute("data-story-id"))));
				return;
			}

			const observer = new IntersectionObserver(
				(entries) => {
					entries.forEach((entry) => {
						if (!entry.isIntersecting) {
							return;
						}

						const storyId = Number(entry.target.getAttribute("data-story-id"));
						this.trackImpression(storyId);
						observer.unobserve(entry.target);
					});
				},
				{ threshold: 0.6 }
			);

			this.items.forEach((item) => observer.observe(item));
		}

		open(index) {
			if (!this.slides[index]) {
				return;
			}

			if (namespace.activeInstance && namespace.activeInstance !== this) {
				namespace.activeInstance.close({ restoreFocus: false });
			}

			namespace.activeInstance = this;
			this.lastFocusedElement = document.activeElement instanceof HTMLElement ? document.activeElement : null;
			this.viewer.hidden = false;
			this.viewer.setAttribute("aria-hidden", "false");
			document.body.classList.add("prime-stories-viewer-open");
			document.addEventListener("keydown", this.keydownHandler);
			document.addEventListener("visibilitychange", this.visibilityHandler);
			this.showSlide(index, true);
			if (this.dialog) {
				this.dialog.focus({ preventScroll: true });
			}
		}

		close(options) {
			const settings = options || {};

			this.stopTimer();
			this.pauseCurrentVideo();
			this.viewer.hidden = true;
			this.viewer.setAttribute("aria-hidden", "true");
			document.body.classList.remove("prime-stories-viewer-open");
			document.removeEventListener("keydown", this.keydownHandler);
			document.removeEventListener("visibilitychange", this.visibilityHandler);
			this.items.forEach((item) => item.classList.remove("is-active"));
			this.slides.forEach((slide) => {
				slide.hidden = true;
			});
			this.activeIndex = -1;
			this.isPaused = false;
			this.wasPlayingVideo = false;
			this.updateMuteButton(null);

			if (namespace.activeInstance === this) {
				namespace.activeInstance = null;
			}

			if (settings.restoreFocus !== false && this.lastFocusedElement && typeof this.lastFocusedElement.focus === "function") {
				this.lastFocusedElement.focus({ preventScroll: true });
			}
		}

		showSlide(index, resetProgress) {
			if (!this.slides[index]) {
				this.close();
				return;
			}

			this.stopTimer();
			this.pauseCurrentVideo();

			this.activeIndex = index;
			this.slides.forEach((slide, slideIndex) => {
				slide.hidden = slideIndex !== index;
			});

			this.items.forEach((item) => {
				const start = Number(item.getAttribute("data-story-index")) || 0;
				const count = Number(item.getAttribute("data-story-slide-count")) || 1;
				item.classList.toggle("is-active", index >= start && index < start + count);
			});

			if (resetProgress) {
				this.elapsed = 0;
			}

			this.renderProgress(0);

			const slide = this.slides[index];
			const storyId = Number(slide.getAttribute("data-story-id"));
			this.applyFitMode(slide);
			this.markSlideSeen(slide);
			this.trackEvent("open", storyId, this.getSlideMeta(slide));
			this.loadSlideMedia(slide);
			this.loadAdjacentMedia(index);

			const video = slide.querySelector("video");
			if (video) {
				this.activateVideo(video, storyId, slide);
				return;
			}

			const durationSeconds = Number(slide.getAttribute("data-duration")) || 5;
			if (this.autoplay) {
				this.startTimer(durationSeconds * 1000, storyId, slide);
			}
		}

		applyFitMode(slide) {
			const fitMode = slide.getAttribute("data-fit-mode") === "contain" ? "contain" : "cover";
			this.dialog.classList.toggle("prime-stories-fit-contain", fitMode === "contain");
			this.dialog.classList.toggle("prime-stories-fit-cover", fitMode !== "contain");
		}

		loadAdjacentMedia(index) {
			[index - 1, index + 1].forEach((targetIndex) => {
				if (this.slides[targetIndex]) {
					this.loadSlideMedia(this.slides[targetIndex]);
				}
			});
		}

		loadSlideMedia(slide) {
			const media = slide.querySelector(".prime-stories-media");
			if (!media) {
				return;
			}

			const source = chooseSource(media);
			if (!source) {
				return;
			}

			if (media.tagName === "IMG") {
				if (!media.dataset.primeStoriesErrorBound) {
					media.dataset.primeStoriesErrorBound = "true";
					media.addEventListener("error", () => {
						reportClientIssue(
							"Story image failed to load.",
							{
								storyId: Number(slide.getAttribute("data-story-id")),
								source: media.getAttribute("src") || source,
							},
							"warning",
							"viewer.media_image"
						);
					});
				}

				if (media.getAttribute("src") !== source) {
					media.setAttribute("src", source);
				}
				return;
			}

			if (!media.dataset.primeStoriesErrorBound) {
				media.dataset.primeStoriesErrorBound = "true";
				media.addEventListener("error", () => {
					reportClientIssue(
						"Story video failed to load.",
						{
							storyId: Number(slide.getAttribute("data-story-id")),
							source: media.currentSrc || media.getAttribute("src") || source,
						},
						"warning",
						"viewer.media_video"
					);
				});
			}

			if (media.getAttribute("src") !== source) {
				media.setAttribute("src", source);
				media.load();
			}
		}

		activateVideo(video, storyId, slide) {
			this.updateMuteButton(video);
			video.muted = this.isMuted;
			video.currentTime = 0;

			video.onended = () => {
				this.completeSlide(storyId, slide || video.closest("[data-story-slide]"));
				this.next();
			};

			video.ontimeupdate = () => {
				if (!video.duration || Number.isNaN(video.duration)) {
					return;
				}

				this.renderProgress(video.currentTime / video.duration);
			};

			if (!this.autoplay) {
				return;
			}

			const playPromise = video.play();
			if (playPromise && typeof playPromise.catch === "function") {
				playPromise.catch(() => {
					const slide = video.closest("[data-story-slide]");
					this.startTimer((Number(slide && slide.getAttribute("data-duration")) || 5) * 1000, storyId, slide);
				});
			}
		}

		pauseCurrentVideo() {
			const activeSlide = this.slides[this.activeIndex];
			if (!activeSlide) {
				return;
			}

			const video = activeSlide.querySelector("video");
			if (video) {
				video.pause();
				video.onended = null;
				video.ontimeupdate = null;
			}
		}

		updateMuteButton(video) {
			if (!this.muteButton) {
				return;
			}

			if (!video) {
				this.muteButton.hidden = true;
				return;
			}

			this.muteButton.hidden = false;
			this.muteButton.classList.toggle("is-muted", this.isMuted);
			this.muteButton.setAttribute("aria-label", this.isMuted ? getLabel("unmute", "Unmute video") : getLabel("mute", "Mute video"));
		}

		toggleMute() {
			const activeSlide = this.slides[this.activeIndex];
			if (!activeSlide) {
				return;
			}

			const video = activeSlide.querySelector("video");
			if (!video) {
				return;
			}

			this.isMuted = !this.isMuted;
			video.muted = this.isMuted;
			this.updateMuteButton(video);
		}

		startTimer(duration, storyId, slide) {
			this.stopTimer(false);
			this.progressDuration = duration;
			this.startedAt = performance.now();
			this.isPaused = false;

			const tick = (timestamp) => {
				if (this.isPaused) {
					return;
				}

				const runtime = this.elapsed + (timestamp - this.startedAt);
				const ratio = Math.min(runtime / this.progressDuration, 1);
				this.renderProgress(ratio);

				if (ratio >= 1) {
					this.elapsed = 0;
					this.completeSlide(storyId, slide || this.slides[this.activeIndex]);
					this.next();
					return;
				}

				this.animationFrame = window.requestAnimationFrame(tick);
			};

			this.animationFrame = window.requestAnimationFrame(tick);
		}

		stopTimer(resetElapsed) {
			if (this.animationFrame) {
				window.cancelAnimationFrame(this.animationFrame);
				this.animationFrame = null;
			}

			if (resetElapsed !== false) {
				this.elapsed = 0;
				this.progressDuration = 0;
			}
		}

		pause() {
			if (this.viewer.hidden || this.isPaused || this.activeIndex < 0) {
				return;
			}

			const activeSlide = this.slides[this.activeIndex];
			const video = activeSlide ? activeSlide.querySelector("video") : null;
			const hasRunningTimer = this.progressDuration > 0 && !!this.animationFrame;
			const hasPlayingVideo = video && !video.paused && !video.ended;

			if (!hasRunningTimer && !hasPlayingVideo) {
				return;
			}

			this.isPaused = true;
			this.wasPlayingVideo = Boolean(hasPlayingVideo);

			if (this.progressDuration && this.startedAt) {
				this.elapsed += performance.now() - this.startedAt;
			}

			if (this.animationFrame) {
				window.cancelAnimationFrame(this.animationFrame);
				this.animationFrame = null;
			}

			if (video) {
				video.pause();
			}
		}

		resume() {
			if (this.viewer.hidden || !this.isPaused || this.activeIndex < 0) {
				return;
			}

			this.isPaused = false;
			const activeSlide = this.slides[this.activeIndex];
			if (!activeSlide) {
				return;
			}

			const storyId = Number(activeSlide.getAttribute("data-story-id"));
			const video = activeSlide.querySelector("video");
			if (video) {
				if (this.wasPlayingVideo || this.autoplay) {
					video.play().catch(function () {
						return null;
					});
				}
				this.wasPlayingVideo = false;
				return;
			}

			if (!this.progressDuration) {
				return;
			}

			this.startedAt = performance.now();
			this.startTimer(Math.max(this.progressDuration || 1, 1), storyId, activeSlide);
		}

		getSlideMeta(slide) {
			return {
				slide: slide ? slide.getAttribute("data-slide-id") || "" : "",
			};
		}

		completeSlide(storyId, slide) {
			this.trackEvent("complete", storyId, this.getSlideMeta(slide));
			this.markSlideSeen(slide);
		}

		renderProgress(currentRatio) {
			this.progressBars.forEach((bar, index) => {
				if (index < this.activeIndex) {
					bar.style.width = "100%";
					return;
				}

				if (index > this.activeIndex) {
					bar.style.width = "0%";
					return;
				}

				bar.style.width = Math.max(0, Math.min(currentRatio, 1)) * 100 + "%";
			});
		}

		previous() {
			if (this.activeIndex <= 0) {
				this.showSlide(0, true);
				return;
			}

			this.showSlide(this.activeIndex - 1, true);
		}

		next() {
			if (this.activeIndex >= this.slides.length - 1) {
				this.close();
				return;
			}

			this.showSlide(this.activeIndex + 1, true);
		}

		handleKeydown(event) {
			if (this.viewer.hidden || namespace.activeInstance !== this) {
				return;
			}

			if (event.key === "Escape") {
				event.preventDefault();
				this.close();
			} else if (event.key === "Tab") {
				this.trapFocus(event);
			} else if (event.key === "ArrowRight") {
				event.preventDefault();
				this.next();
			} else if (event.key === "ArrowLeft") {
				event.preventDefault();
				this.previous();
			}
		}

		trapFocus(event) {
			const focusable = Array.from(
				this.dialog.querySelectorAll('a[href], button:not([disabled]):not([hidden]), [tabindex]:not([tabindex="-1"])')
			).filter((element) => element instanceof HTMLElement && element.offsetParent !== null);

			if (!focusable.length) {
				event.preventDefault();
				this.dialog.focus({ preventScroll: true });
				return;
			}

			const first = focusable[0];
			const last = focusable[focusable.length - 1];

			if (event.shiftKey && document.activeElement === first) {
				event.preventDefault();
				last.focus({ preventScroll: true });
			} else if (!event.shiftKey && document.activeElement === last) {
				event.preventDefault();
				first.focus({ preventScroll: true });
			}
		}

		handleVisibilityChange() {
			if (document.hidden) {
				this.pause();
			} else {
				this.resume();
			}
		}

		markSlideSeen(slide) {
			if (!slide) {
				return;
			}

			const slideId = slide.getAttribute("data-slide-id") || "";
			const storyId = Number(slide.getAttribute("data-story-id"));
			if (!slideId || !storyId) {
				return;
			}

			this.seenSlideIds.add(slideId);
			storeStringSet(seenSlideStorageKey, this.seenSlideIds);

			if (this.isStoryFullySeen(storyId)) {
				this.markSeen(storyId);
			}
		}

		isStoryFullySeen(storyId) {
			const item = this.items.find((candidate) => Number(candidate.getAttribute("data-story-id")) === storyId);
			if (!item) {
				return false;
			}

			const start = Number(item.getAttribute("data-story-index")) || 0;
			const count = Number(item.getAttribute("data-story-slide-count")) || 1;
			for (let index = start; index < start + count; index += 1) {
				const slide = this.slides[index];
				const slideId = slide ? slide.getAttribute("data-slide-id") || "" : "";
				if (!slideId || !this.seenSlideIds.has(slideId)) {
					return false;
				}
			}

			return true;
		}

		markSeen(storyId) {
			if (!config.enableSeenState || doNotTrack || !storyId) {
				return;
			}

			this.seenIds.add(storyId);
			storeSeenIds(this.seenIds);

			this.items.forEach((item) => {
				if (Number(item.getAttribute("data-story-id")) !== storyId) {
					return;
				}

				item.classList.add("prime-stories-item-seen");
				item.classList.remove("prime-stories-item-unseen");
			});

			if ((config.isUserLoggedIn || config.enableGuestSeen) && !this.remoteSeenIds.has(storyId)) {
				this.remoteSeenIds.add(storyId);
				request("/seen", {
					story_id: storyId,
					session_id: this.sessionId,
				});
			}
		}

		trackImpression(storyId) {
			if (!config.enableAnalytics || doNotTrack || !storyId || this.sentImpressions.has(storyId)) {
				return;
			}

			this.sentImpressions.add(storyId);
			this.trackEvent("impression", storyId);
		}

		trackEvent(eventType, storyId, meta) {
			if (!config.enableAnalytics || doNotTrack || !storyId) {
				return;
			}

			request("/track", {
				story_id: storyId,
				event_type: eventType,
				session_id: this.sessionId,
				source: this.instanceId || this.wrapper.getAttribute("data-layout") || "viewer",
				meta: meta || {},
			});
		}
	}

	function collectWrappers(root) {
		if (!root) {
			return [];
		}

		if (root instanceof Element && root.matches("[data-prime-stories]")) {
			return [root].concat(Array.from(root.querySelectorAll("[data-prime-stories]")));
		}

		return Array.from(root.querySelectorAll ? root.querySelectorAll("[data-prime-stories]") : []);
	}

	function init(root) {
		const wrappers = collectWrappers(root || document);
		const instances = [];

		wrappers.forEach((wrapper) => {
			if (wrapper.dataset.primeStoriesInitialized === "true") {
				return;
			}

			try {
				const instance = new PrimeStoriesInstance(wrapper);
				if (instance && instance.wrapper) {
					instances.push(instance);
				}
			} catch (error) {
				reportClientIssue(
					"Story viewer initialization failed.",
					{
						error: stringifyError(error),
						wrapperId: wrapper.id || "",
					},
					"error",
					"viewer.init"
				);
			}
		});

		if (!Array.isArray(namespace.instances)) {
			namespace.instances = [];
		}

		namespace.instances = namespace.instances.concat(instances);
		return instances;
	}

	function bindElementorHooks() {
		if (namespace.elementorHookBound || !window.elementorFrontend || !window.elementorFrontend.hooks) {
			return;
		}

		namespace.elementorHookBound = true;
		window.elementorFrontend.hooks.addAction("frontend/element_ready/global", function (scope) {
			if (scope && scope[0]) {
				init(scope[0]);
			}
		});
	}

	function bindGlobalDiagnostics() {
		if (namespace.globalDiagnosticsBound) {
			return;
		}

		namespace.globalDiagnosticsBound = true;

		window.addEventListener(
			"error",
			function (event) {
				const filename = event && event.filename ? String(event.filename) : "";
				const stack = event && event.error && event.error.stack ? String(event.error.stack) : "";

				if (filename.indexOf("prime-stories") === -1 && stack.indexOf("prime-stories") === -1) {
					return;
				}

				reportClientIssue(
					"Unhandled frontend error captured.",
					{
						message: event.message || "",
						filename: filename,
						line: event.lineno || 0,
						column: event.colno || 0,
						error: stringifyError(event.error),
					},
					"error",
					"viewer.window_error"
				);
			},
			true
		);

		window.addEventListener("unhandledrejection", function (event) {
			const reason = event && event.reason ? event.reason : "";
			const detail = stringifyError(reason);
			if (detail.indexOf("prime-stories") === -1 && detail.indexOf("prime-stories-public") === -1) {
				return;
			}

			reportClientIssue(
				"Unhandled promise rejection captured.",
				{
					reason: detail,
				},
				"error",
				"viewer.promise_rejection"
			);
		});
	}

	namespace.init = init;
	namespace.instances = Array.isArray(namespace.instances) ? namespace.instances : [];
	namespace.reportIssue = reportClientIssue;

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", function () {
			init(document);
			bindElementorHooks();
			bindGlobalDiagnostics();
		});
	} else {
		init(document);
		bindElementorHooks();
		bindGlobalDiagnostics();
	}

	if (window.jQuery) {
		window.jQuery(window).on("elementor/frontend/init", function () {
			bindElementorHooks();
		});
	}
})();
