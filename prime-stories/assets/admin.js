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
		const imageInput = row.querySelector('input[name$="[image_id]"]');
		const videoInput = row.querySelector('input[name$="[video_id]"]');
		const actionPayload = row.querySelector("[data-slide-action-payload-field]");
		const title = row.querySelector('input[name$="[title]"]');
		const duration = row.querySelector('input[name$="[duration]"]');
		const previewTitle = row.querySelector("[data-slide-preview-title]");
		const previewMeta = row.querySelector("[data-slide-preview-meta]");

		if (imageInput) {
			const imageField = imageInput.closest(".prime-stories-media-field");
			if (imageField) {
				imageField.hidden = mediaType && mediaType.value === "video";
			}
		}

		if (videoInput) {
			const videoField = videoInput.closest(".prime-stories-media-field");
			if (videoField) {
				videoField.hidden = !mediaType || mediaType.value !== "video";
			}
		}

		if (actionPayload) {
			actionPayload.hidden = !actionType || actionType.value === "none" || actionType.value === "reaction";
		}

		if (previewTitle && title) {
			previewTitle.textContent = title.value.trim() || getLabel("untitledSlide", "Untitled slide");
		}

		if (previewMeta) {
			previewMeta.textContent = [duration && duration.value ? duration.value + "s" : "", actionType ? actionType.value : ""].filter(Boolean).join(" - ");
		}
	}

	function syncSlideRows() {
		if (!slidesContainer) {
			return;
		}

		Array.from(slidesContainer.querySelectorAll("[data-slide-row]")).forEach(syncSlideRow);
	}

	document.addEventListener("input", function (event) {
		if (event.target && event.target.closest("[data-slide-row]")) {
			syncSlideRow(event.target.closest("[data-slide-row]"));
		}
	});

	syncSlideRows();
})();
