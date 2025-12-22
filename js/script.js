document.addEventListener("DOMContentLoaded", () => {
  function setupTabs(tabSelector, paneSelector, activeClass, scopeToClosest = null) {
    const tabs = document.querySelectorAll(tabSelector);

    tabs.forEach(tab => {
      tab.addEventListener("click", () => {
        let scopeElement = document;

        if (scopeToClosest) {
          const closestParent = tab.closest(scopeToClosest);
          if (closestParent) {
            scopeElement = closestParent;
          }
        }

        const scopedTabs = scopeElement.querySelectorAll(tabSelector);
        const scopedPanes = scopeElement.querySelectorAll(paneSelector);

        scopedTabs.forEach(t => t.classList.remove(activeClass));
        scopedPanes.forEach(p => p.classList.remove(activeClass));

        tab.classList.add(activeClass);
        const target = scopeElement.querySelector(`#${tab.dataset.tab}`);
        if (target) {
          target.classList.add(activeClass);
        }
      });
    });
  }

  setupTabs(".qr-tab", ".qr-tab-pane", "active");
  setupTabs(".qr-inner-tab", ".qr-inner-tab-pane", "active-inner-tab", ".qr-tab-pane");
});


const buttons = document.querySelectorAll('.btn-logos');
buttons.forEach(button => {
  button.addEventListener('click', () => {
    buttons.forEach(btn => btn.classList.remove('active-logo'));
    button.classList.add('active-logo');
  });
});

document.body.classList.toggle(
  'supports-custom-select',
  CSS.supports("appearance: base-select")
);

const colorspace = document.getElementById("colorspace");
if (colorspace) {
  colorspace.oninput = e => {
    console.log("Colorspace changed to:", e.target.value);
  };
}

const colorpicker = document.getElementById("colorpicker");
const hexcolor = document.getElementById("hexcolor");

if (colorpicker && hexcolor) {
  colorpicker.addEventListener("input", function () {
    hexcolor.value = this.value;
  });

  hexcolor.addEventListener("input", function () {
    colorpicker.value = this.value;
  });
}


// document.addEventListener("DOMContentLoaded", function () {
//   const tabs = document.querySelectorAll(".cust_social_wrap ul li");

//   tabs.forEach(tab => {
//     tab.addEventListener("click", function () {
//       const targetId = this.getAttribute("data-tab");

//       // Remove active class from all tabs and fields
//       tabs.forEach(t => t.classList.remove("qr-active-social"));
//       document.querySelectorAll(".qr-social-feild").forEach(f => f.classList.remove("qr-active-social"));

//       // Add active class to the clicked tab and its field
//       this.classList.add("qr-active-social");
//       const targetField = document.getElementById(targetId);
//       if (targetField) {
//         targetField.classList.add("qr-active-social");
//       }
//     });
//   });

//   // Close button logic remains the same
//   document.querySelectorAll(".socail-close-icon").forEach(closeBtn => {
//     closeBtn.addEventListener("click", function (e) {
//       e.stopPropagation();

//       const parentField = this.closest(".qr-social-feild");
//       if (parentField) {
//         parentField.classList.remove("qr-active-social");

//         const fieldId = parentField.getAttribute("id");
//         const matchingTab = document.querySelector(`.cust_social_wrap ul li[data-tab="${fieldId}"]`);
//         if (matchingTab) {
//           matchingTab.classList.remove("qr-active-social");
//         }
//       }
//     });
//   });
// });

