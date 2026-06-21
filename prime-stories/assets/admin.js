(function () {
	if (typeof window === "undefined" || typeof document === "undefined") {
		return;
	}

	const adminConfig = window.primeStoriesAdminConfig || {};

	function getLabel(key, fallback) {
		return adminConfig[key] || fallback;
	}

	function createMediaFrame(onSelect, libraryType) {
		if (!window.wp || !window.wp.media) {
			return null;
		}

		const mediaOptions = {
			title: getLabel("mediaTitle", "Choose media"),
			button: {
				text: getLabel("mediaButton", "Use this media"),
			},
			multiple: false,
		};

		if (libraryType === "image" || libraryType === "video") {
			mediaOptions.library = { type: libraryType };
		}

		const frame = window.wp.media(mediaOptions);

		frame.on("select", function () {
			const attachment = frame.state().get("selection").first().toJSON();
			onSelect(attachment);
		});

		return frame;
	}

	function updateMediaPreview(input, attachment) {
		const field = input.closest(".prime-stories-media-field");
		const preview = field ? field.querySelector(".prime-stories-media-preview") : null;
		if (!preview) {
			return;
		}

		preview.textContent = "";

		if (!attachment) {
			const emptyState = document.createElement("span");
			emptyState.textContent = getLabel("emptyMedia", "No media selected");
			preview.appendChild(emptyState);
			return;
		}

		const isImage = attachment.type === "image";
		const previewUrl = (attachment.sizes && attachment.sizes.medium && attachment.sizes.medium.url) || attachment.url || "";

		if (isImage && previewUrl) {
			const image = document.createElement("img");
			image.src = previewUrl;
			image.alt = "";
			preview.appendChild(image);
			const row = input.closest("[data-slide-row]");
			if (row) {
				const previewMedia = row.querySelector(".prime-stories-slide-preview-media");
				if (previewMedia) {
					previewMedia.textContent = "";
					const previewImage = document.createElement("img");
					previewImage.src = previewUrl;
					previewImage.alt = "";
					previewMedia.appendChild(previewImage);
					previewMedia.setAttribute("data-focal-picker", "");
					ensureFocalMarker(row);
				}
				scheduleLivePreview();
			}
			return;
		}

		const label = document.createElement(attachment.url ? "a" : "span");
		label.textContent = attachment.filename || getLabel("mediaChosen", "Media selected");

		if (attachment.url) {
			label.href = attachment.url;
			label.target = "_blank";
			label.rel = "noopener noreferrer";
		}

		preview.appendChild(label);
	}

	document.addEventListener("click", function (event) {
		const selectButton = event.target.closest(".prime-stories-media-select");
		if (selectButton) {
			event.preventDefault();
			const targetId = selectButton.getAttribute("data-target");
			const input = document.getElementById(targetId);
			if (!input) {
				return;
			}

			const frame = createMediaFrame(function (attachment) {
				input.value = attachment.id || "";
				updateMediaPreview(input, attachment);
			}, selectButton.getAttribute("data-library-type") || "media");

			if (frame) {
				frame.open();
			}
			return;
		}

		const removeButton = event.target.closest(".prime-stories-media-remove");
		if (removeButton) {
			event.preventDefault();
			const targetId = removeButton.getAttribute("data-target");
			const input = document.getElementById(targetId);
			if (!input) {
				return;
			}

			input.value = "";
			updateMediaPreview(input, null);
			return;
		}

		const removeRuleButton = event.target.closest(".prime-stories-remove-rule");
		if (removeRuleButton) {
			event.preventDefault();
			const row = removeRuleButton.closest(".prime-stories-rule-row");
			if (row) {
				row.remove();
			}
		}

		const removeSlideButton = event.target.closest(".prime-stories-remove-slide");
		if (removeSlideButton) {
			event.preventDefault();
			const row = removeSlideButton.closest("[data-slide-row]");
			const rows = document.querySelectorAll("[data-slide-row]");
			if (row && rows.length > 1) {
				row.remove();
				renumberSlides();
			}
		}

		const tabButton = event.target.closest("[data-slide-tab]");
		if (tabButton) {
			event.preventDefault();
			const row = tabButton.closest("[data-slide-row]");
			if (row) {
				setActiveSlideTab(row, tabButton.getAttribute("data-slide-tab") || "media");
			}
		}

		const focalPicker = event.target.closest("[data-focal-picker]");
		if (focalPicker) {
			const row = focalPicker.closest("[data-slide-row]");
			const rect = focalPicker.getBoundingClientRect();
			const x = Math.max(0, Math.min(100, Math.round(((event.clientX - rect.left) / rect.width) * 100)));
			const y = Math.max(0, Math.min(100, Math.round(((event.clientY - rect.top) / rect.height) * 100)));
			setField(row, "focal_x", String(x));
			setField(row, "focal_y", String(y));
			updateFocalMarker(row);
			syncSlideRow(row);
			return;
		}

		const resetFocal = event.target.closest("[data-focal-reset]");
		if (resetFocal) {
			event.preventDefault();
			const row = resetFocal.closest("[data-slide-row]");
			setField(row, "focal_x", "50");
			setField(row, "focal_y", "50");
			updateFocalMarker(row);
			syncSlideRow(row);
			return;
		}

		const clickedSlide = event.target.closest("[data-slide-row]");
		if (clickedSlide) {
			activePreviewIndex = Math.max(0, getSlideRows().indexOf(clickedSlide));
			scheduleLivePreview();
		}

		const moveUpButton = event.target.closest(".prime-stories-move-slide-up");
		if (moveUpButton) {
			event.preventDefault();
			const row = moveUpButton.closest("[data-slide-row]");
			if (row && row.previousElementSibling) {
				row.parentNode.insertBefore(row, row.previousElementSibling);
				renumberSlides();
			}
		}

		const moveDownButton = event.target.closest(".prime-stories-move-slide-down");
		if (moveDownButton) {
			event.preventDefault();
			const row = moveDownButton.closest("[data-slide-row]");
			if (row && row.nextElementSibling) {
				row.parentNode.insertBefore(row.nextElementSibling, row);
				renumberSlides();
			}
		}
	});

	const addRuleButton = document.getElementById("prime-stories-add-rule");
	const rulesContainer = document.getElementById("prime-stories-rules-rows");
	const template = document.getElementById("tmpl-prime-stories-rule-row");

	if (addRuleButton && rulesContainer && template) {
		addRuleButton.addEventListener("click", function () {
			const index = Date.now();
			const id =
				typeof crypto !== "undefined" && typeof crypto.randomUUID === "function"
					? crypto.randomUUID()
					: "prime-stories-rule-" + index;

			const html = template.innerHTML.replace(/__INDEX__/g, String(index)).replace(/__ID__/g, id);
			rulesContainer.insertAdjacentHTML("beforeend", html);
		});
	}

	const addSlideButton = document.getElementById("prime-stories-add-slide");
	const slidesContainer = document.getElementById("prime-stories-slide-rows");
	const slideTemplate = document.getElementById("tmpl-prime-stories-slide-row");
	const livePreview = document.querySelector("[data-story-live-preview]");
	let draggedSlide = null;
	let activePreviewIndex = 0;
	let previewTimer = null;

	function getSlideRows() {
		return slidesContainer ? Array.from(slidesContainer.querySelectorAll("[data-slide-row]")) : [];
	}

	function renumberSlides() {
		if (!slidesContainer) {
			return;
		}

		Array.from(slidesContainer.querySelectorAll("[data-slide-row]")).forEach((row, index) => {
			const number = row.querySelector("[data-slide-number]");
			if (number) {
				number.textContent = String(index + 1);
			}
		});
	}

	if (addSlideButton && slidesContainer && slideTemplate) {
		addSlideButton.addEventListener("click", function () {
			const index = Date.now();
			const html = slideTemplate.innerHTML.replace(/__INDEX__/g, String(index));
			slidesContainer.insertAdjacentHTML("beforeend", html);
			renumberSlides();
			syncSlideRows();
		});
	}

	document.addEventListener("click", function (event) {
		const presetButton = event.target.closest("[data-story-preset]");
		if (!presetButton || !slidesContainer || !slideTemplate) {
			return;
		}

		event.preventDefault();
		const row = addSlideFromTemplate();
		applyPreset(row, presetButton.getAttribute("data-story-preset") || "");
	});

	if (slidesContainer) {
		slidesContainer.addEventListener("dragstart", function (event) {
			const row = event.target.closest("[data-slide-row]");
			if (!row) {
				return;
			}

			draggedSlide = row;
			row.classList.add("is-dragging");
			event.dataTransfer.effectAllowed = "move";
		});

		slidesContainer.addEventListener("dragover", function (event) {
			if (!draggedSlide) {
				return;
			}

			event.preventDefault();
			const target = event.target.closest("[data-slide-row]");
			if (!target || target === draggedSlide) {
				return;
			}

			const box = target.getBoundingClientRect();
			const after = event.clientY > box.top + box.height / 2;
			slidesContainer.insertBefore(draggedSlide, after ? target.nextSibling : target);
		});

		slidesContainer.addEventListener("dragend", function () {
			if (draggedSlide) {
				draggedSlide.classList.remove("is-dragging");
			}
			draggedSlide = null;
			renumberSlides();
		});
	}

	if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.wpColorPicker === "function") {
		window.jQuery(function ($) {
			$(".prime-stories-color-field").wpColorPicker();
		});
	}

	function syncMediaTypeFields() {
		const mediaType = document.getElementById("prime_stories_media_type");
		if (!mediaType) {
			return;
		}

		const imageField = document.querySelector(".prime-stories-media-field-image_id");
		const videoField = document.querySelector(".prime-stories-media-field-video_id");

		if (imageField) {
			imageField.hidden = mediaType.value === "video";
		}

		if (videoField) {
			videoField.hidden = mediaType.value !== "video";
		}
	}

	document.addEventListener("change", function (event) {
		if (event.target && event.target.id === "prime_stories_media_type") {
			syncMediaTypeFields();
		}

		if (event.target && event.target.closest("[data-slide-row]")) {
			syncSlideRow(event.target.closest("[data-slide-row]"));
		}
	});

	syncMediaTypeFields();

	function syncSlideRow(row) {
		if (!row) {
			return;
		}

		const mediaType = row.querySelector('select[name$="[media_type]"]');
		const actionType = row.querySelector('select[name$="[action_type]"]');
		const activeTab = (row.querySelector("[data-slide-tab].is-active") || {}).getAttribute
			? row.querySelector("[data-slide-tab].is-active").getAttribute("data-slide-tab")
			: "media";
		const imageInput = row.querySelector('input[name$="[image_id]"]');
		const videoInput = row.querySelector('input[name$="[video_id]"]');
		const actionPayload = row.querySelector("[data-slide-action-payload-field]");
		const title = row.querySelector('input[name$="[title]"]');
		const duration = row.querySelector('input[name$="[duration]"]');
		const previewTitle = row.querySelector("[data-slide-preview-title]");
		const previewMeta = row.querySelector("[data-slide-preview-meta]");
		const buttonText = row.querySelector('input[name$="[button_text]"]');
		const buttonUrl = row.querySelector('input[name$="[button_url]"]');
		updateFocalMarker(row);

		if (imageInput) {
			const imageField = imageInput.closest(".prime-stories-media-field");
			if (imageField) {
				imageField.hidden = activeTab !== "media" || (mediaType && mediaType.value === "video");
			}
		}

		if (videoInput) {
			const videoField = videoInput.closest(".prime-stories-media-field");
			if (videoField) {
				videoField.hidden = activeTab !== "media" || !mediaType || mediaType.value !== "video";
			}
		}

		if (actionPayload) {
			actionPayload.hidden = activeTab !== "action" || !actionType || actionType.value === "none" || actionType.value === "reaction";
		}

		const pollOptions = row.querySelector("[data-slide-poll-options-field]");
		if (pollOptions) {
			pollOptions.hidden = activeTab !== "action" || !actionType || actionType.value !== "poll";
		}

		const countdownInput = row.querySelector('input[name$="[countdown_datetime]"]');
		if (countdownInput) {
			const countdownField = countdownInput.closest(".prime-stories-admin-field");
			if (countdownField) {
				countdownField.hidden = activeTab !== "action" || !actionType || actionType.value !== "countdown";
			}
		}

		const replyPlaceholder = row.querySelector('input[name$="[reply_placeholder]"]');
		if (replyPlaceholder) {
			const replyField = replyPlaceholder.closest(".prime-stories-admin-field");
			if (replyField) {
				replyField.hidden = activeTab !== "action" || !actionType || actionType.value !== "question";
			}
		}

		if (previewTitle && title) {
			previewTitle.textContent = title.value.trim() || getLabel("untitledSlide", "Untitled slide");
		}

		if (previewMeta) {
			previewMeta.textContent = [duration && duration.value ? duration.value + "s" : "", actionType ? actionType.value : "", buttonText && buttonText.value && buttonUrl && buttonUrl.value ? "CTA" : ""].filter(Boolean).join(" - ");
		}

		validateSlideRow(row);
		scheduleLivePreview();
	}

	function ensureFocalMarker(row) {
		const media = row ? row.querySelector(".prime-stories-slide-preview-media") : null;
		if (!media) {
			return;
		}

		if (!media.querySelector(".prime-stories-focal-marker")) {
			const marker = document.createElement("span");
			marker.className = "prime-stories-focal-marker";
			marker.setAttribute("aria-hidden", "true");
			media.appendChild(marker);
		}

		if (!media.querySelector("[data-focal-reset]")) {
			const reset = document.createElement("button");
			reset.type = "button";
			reset.className = "button button-small prime-stories-focal-reset";
			reset.setAttribute("data-focal-reset", "");
			reset.textContent = getLabel("resetFocal", "Reset focus");
			media.appendChild(reset);
		}
	}

	function updateFocalMarker(row) {
		const media = row ? row.querySelector(".prime-stories-slide-preview-media") : null;
		if (!media) {
			return;
		}

		const image = media.querySelector("img");
		if (!image) {
			return;
		}

		media.setAttribute("data-focal-picker", "");
		ensureFocalMarker(row);
		const x = getRowValue(row, "focal_x") || "50";
		const y = getRowValue(row, "focal_y") || "50";
		image.style.objectPosition = x + "% " + y + "%";

		const marker = media.querySelector(".prime-stories-focal-marker");
		if (marker) {
			marker.style.left = x + "%";
			marker.style.top = y + "%";
		}
	}

	function setActiveSlideTab(row, tabName) {
		row.querySelectorAll("[data-slide-tab]").forEach((button) => {
			button.classList.toggle("is-active", button.getAttribute("data-slide-tab") === tabName);
		});

		row.querySelectorAll("[data-slide-panel]").forEach((field) => {
			field.hidden = field.getAttribute("data-slide-panel") !== tabName;
		});

		syncSlideRow(row);
	}

	function addSlideFromTemplate() {
		const index = Date.now();
		const html = slideTemplate.innerHTML.replace(/__INDEX__/g, String(index));
		slidesContainer.insertAdjacentHTML("beforeend", html);
		const row = slidesContainer.lastElementChild;
		renumberSlides();
		syncSlideRows();
		return row;
	}

	function setField(row, suffix, value) {
		const field = row ? row.querySelector('[name$="[' + suffix + ']"]') : null;
		if (field) {
			field.value = value;
		}
	}

	function getRowValue(row, suffix) {
		const field = row ? row.querySelector('[name$="[' + suffix + ']"]') : null;
		return field ? field.value || "" : "";
	}

	function applyPreset(row, preset) {
		if (!row) {
			return;
		}

		if (preset === "product") {
			setField(row, "title", "Product story");
			setField(row, "subtitle", "New arrival");
			setField(row, "button_text", "View product");
			setField(row, "duration", "5");
			setField(row, "action_type", "reaction");
		} else if (preset === "poll") {
			setField(row, "title", "Quick poll");
			setField(row, "action_type", "poll");
			setField(row, "action_payload", "Which one do you prefer?");
			setField(row, "poll_options", "Option A\nOption B");
		} else if (preset === "countdown") {
			setField(row, "title", "Coming soon");
			setField(row, "action_type", "countdown");
			setField(row, "action_payload", "Launch starts in");
		}

		setActiveSlideTab(row, preset === "product" ? "cta" : "action");
		syncSlideRow(row);
	}

	function validateSlideRow(row) {
		const mediaType = row.querySelector('select[name$="[media_type]"]');
		const imageInput = row.querySelector('input[name$="[image_id]"]');
		const videoInput = row.querySelector('input[name$="[video_id]"]');
		const actionType = row.querySelector('select[name$="[action_type]"]');
		const pollOptions = row.querySelector('textarea[name$="[poll_options]"]');
		const countdownInput = row.querySelector('input[name$="[countdown_datetime]"]');
		const warnings = [];

		if (mediaType && mediaType.value === "video" && videoInput && !videoInput.value) {
			warnings.push(getLabel("missingVideo", "Video media is missing."));
		}

		if (mediaType && mediaType.value === "image" && imageInput && !imageInput.value) {
			warnings.push(getLabel("missingImage", "Image media is missing."));
		}

		if (actionType && actionType.value === "poll") {
			const options = pollOptions && pollOptions.value ? pollOptions.value.split(/\r?\n/).filter((option) => option.trim()) : [];
			if (options.length < 2) {
				warnings.push(getLabel("missingPollOptions", "Add at least two poll options."));
			} else if (options.length > 5) {
				warnings.push(getLabel("tooManyPollOptions", "Use no more than five poll options."));
			}
		}

		if (actionType && actionType.value === "countdown" && countdownInput && !countdownInput.value) {
			warnings.push(getLabel("missingCountdown", "Countdown date is missing."));
		}

		const buttonText = row.querySelector('input[name$="[button_text]"]');
		const buttonUrl = row.querySelector('input[name$="[button_url]"]');
		if (buttonText && buttonText.value.trim() && buttonUrl && !buttonUrl.value.trim()) {
			warnings.push(getLabel("missingCtaUrl", "CTA URL is missing."));
		}
		if (buttonUrl && buttonUrl.value.trim() && buttonText && !buttonText.value.trim()) {
			warnings.push(getLabel("missingCtaLabel", "CTA label is missing."));
		}

		let warningBox = row.querySelector("[data-slide-warnings]");
		if (!warningBox) {
			warningBox = document.createElement("div");
			warningBox.className = "prime-stories-slide-warnings";
			warningBox.setAttribute("data-slide-warnings", "");
			row.appendChild(warningBox);
		}

		warningBox.hidden = warnings.length === 0;
		warningBox.textContent = warnings.join(" ");
	}

	function syncSlideRows() {
		if (!slidesContainer) {
			return;
		}

		Array.from(slidesContainer.querySelectorAll("[data-slide-row]")).forEach((row) => {
			setActiveSlideTab(row, "media");
			syncSlideRow(row);
		});
	}

	document.addEventListener("input", function (event) {
		if (event.target && event.target.closest("[data-slide-row]")) {
			syncSlideRow(event.target.closest("[data-slide-row]"));
		}
	});

	syncSlideRows();

	if (livePreview) {
		const prev = livePreview.querySelector("[data-preview-prev]");
		const next = livePreview.querySelector("[data-preview-next]");
		if (prev) {
			prev.addEventListener("click", function () {
				activePreviewIndex = Math.max(0, activePreviewIndex - 1);
				renderLivePreview();
			});
		}
		if (next) {
			next.addEventListener("click", function () {
				activePreviewIndex = Math.min(getSlideRows().length - 1, activePreviewIndex + 1);
				renderLivePreview();
			});
		}
		renderLivePreview();
	}

	function scheduleLivePreview() {
		if (!livePreview) {
			return;
		}

		window.clearTimeout(previewTimer);
		previewTimer = window.setTimeout(renderLivePreview, 80);
	}

	function renderLivePreview() {
		if (!livePreview) {
			return;
		}

		const rows = getSlideRows();
		activePreviewIndex = Math.max(0, Math.min(activePreviewIndex, Math.max(0, rows.length - 1)));
		const row = rows[activePreviewIndex] || null;
		const progress = livePreview.querySelector("[data-preview-progress]");
		const media = livePreview.querySelector("[data-preview-media]");
		const title = livePreview.querySelector("[data-preview-title]");
		const subtitle = livePreview.querySelector("[data-preview-subtitle]");
		const caption = livePreview.querySelector("[data-preview-caption]");
		const cta = livePreview.querySelector("[data-preview-cta]");
		const action = livePreview.querySelector("[data-preview-action]");

		if (progress) {
			progress.innerHTML = rows
				.map((unused, index) => '<span class="' + (index < activePreviewIndex ? "is-complete" : index === activePreviewIndex ? "is-active" : "") + '"><b></b></span>')
				.join("");
		}

		if (!row) {
			if (media) {
				media.innerHTML = "<span>" + escapeHtml(getLabel("emptySlides", "No slides yet.")) + "</span>";
			}
			return;
		}

		const previewImg = row.querySelector(".prime-stories-slide-preview-media img");
		const fitMode = getRowValue(row, "fit_mode") === "contain" ? "contain" : "cover";
		const focalX = getRowValue(row, "focal_x") || "50";
		const focalY = getRowValue(row, "focal_y") || "50";

		if (media) {
			media.innerHTML = "";
			if (previewImg && previewImg.src) {
				const img = document.createElement("img");
				img.src = previewImg.src;
				img.alt = "";
				img.style.objectFit = fitMode;
				img.style.objectPosition = focalX + "% " + focalY + "%";
				media.appendChild(img);
			} else {
				const empty = document.createElement("span");
				empty.textContent = getLabel("emptyMedia", "No media selected");
				media.appendChild(empty);
			}
		}

		if (title) {
			title.textContent = getRowValue(row, "title");
		}
		if (subtitle) {
			subtitle.textContent = getRowValue(row, "subtitle");
		}
		if (caption) {
			caption.textContent = getRowValue(row, "caption");
		}

		if (cta) {
			const label = getRowValue(row, "button_text");
			cta.hidden = !label;
			cta.textContent = label;
		}

		if (action) {
			const actionType = getRowValue(row, "action_type");
			action.hidden = !actionType || actionType === "none";
			action.innerHTML = buildPreviewAction(row, actionType);
		}
	}

	function buildPreviewAction(row, actionType) {
		const prompt = getRowValue(row, "action_payload");
		if (actionType === "reaction") {
			return '<button type="button">Like</button><button type="button">Love</button><button type="button">Wow</button>';
		}
		if (actionType === "poll") {
			const options = (getRowValue(row, "poll_options") || "Yes\nNo").split(/\r?\n/).filter((option) => option.trim()).slice(0, 5);
			return "<p>" + escapeHtml(prompt || "What do you think?") + "</p>" + options.map((option) => {
				const parts = option.split("|");
				const color = /^#[0-9a-f]{3,6}$/i.test((parts[1] || "").trim()) ? ' style="--poll-color:' + parts[1].trim() + '"' : "";
				return "<button type=\"button\"" + color + "><span>" + escapeHtml(parts[0].trim()) + "</span><small>0%</small></button>";
			}).join("");
		}
		if (actionType === "question") {
			return "<p>" + escapeHtml(prompt || "Send a reply") + "</p><input type=\"text\" disabled placeholder=\"" + escapeHtml(getRowValue(row, "reply_placeholder") || "Write a reply...") + "\">";
		}
		if (actionType === "countdown") {
			return "<p>" + escapeHtml(prompt || "Countdown") + "</p><strong>" + escapeHtml(getRowValue(row, "countdown_datetime") || "00h 00m") + "</strong>";
		}
		return "";
	}

	function escapeHtml(value) {
		return String(value || "").replace(/[&<>"']/g, function (char) {
			return {
				"&": "&amp;",
				"<": "&lt;",
				">": "&gt;",
				'"': "&quot;",
				"'": "&#039;",
			}[char];
		});
	}
})();
