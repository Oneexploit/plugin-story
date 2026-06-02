(function () {
	if (typeof window === "undefined" || typeof document === "undefined") {
		return;
	}

	const adminConfig = window.primeStoriesAdminConfig || {};

	function getLabel(key, fallback) {
		return adminConfig[key] || fallback;
	}

	function createMediaFrame(onSelect) {
		if (!window.wp || !window.wp.media) {
			return null;
		}

		const frame = window.wp.media({
			title: getLabel("mediaTitle", "Choose media"),
			button: {
				text: getLabel("mediaButton", "Use this media"),
			},
			multiple: false,
		});

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
			});

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

	if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.wpColorPicker === "function") {
		window.jQuery(function ($) {
			$(".prime-stories-color-field").wpColorPicker();
		});
	}
})();
