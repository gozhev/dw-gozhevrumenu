function foldButtonSetText(button, folded) {
	if (folded) {
		button.innerHTML = "+";
	} else {
		button.innerHTML = "−";
	}
}

function foldButtonFindList(button) {
	for (let el = button.nextSibling; el; el = el.nextSibling) {
		if (el.classList && el.classList.contains("list")) {
			return el;
		}
	}
	return null;
}

function foldButtonIsFolded(button) {
	var list = foldButtonFindList(button);
	if (list) {
		return list.classList.contains("folded");
	}
	return null;
}

function foldButtonToggleList(button) {
	var list = foldButtonFindList(button);
	if (list) {
		let folded = list.classList.toggle("folded");
		foldButtonSetText(button, folded);
	}
}

document.addEventListener("DOMContentLoaded", function() {
	var buttons = document.getElementsByClassName("fold_button");
	for (var i = 0; i < buttons.length; ++i) {
		let button = buttons[i];
		foldButtonSetText(button, foldButtonIsFolded(button));
		var on_click = function(e) {
			if (e.preventDefault) {
				e.preventDefault()
			} else {
				e.returnValue = false;
			}
			foldButtonToggleList(e.target);
		};
		if (button.addEventListener) {
			button.addEventListener("click", on_click);
		} else {
			button.attachEvent("onclick", on_click);
		}
	}
});

document.addEventListener("touchstart", function onTouchStart() {
	document.documentElement.classList.add("touch-device");
	document.removeEventListener("touchstart", onTouchStart);
});

// vim: set ts=4 sw=4 noet :
