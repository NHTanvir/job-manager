jQuery(document).ready((function(i){i("body").hasClass("job-application-details-keep-open")||i(".application_details").hide(),i(document.body).on("click",".job_application .application_button",(function(){var t=i(this).parents(".job_application").find(".application_details").first(),o=i(this);t.slideToggle(400,(function(){if(i(this).is(":visible")){t.trigger("visible");var e=Math.max(Math.min(t.outerHeight(),200),.33*t.outerHeight()),a=t.offset().top+e,n=5;i("#wpadminbar").length>0&&"fixed"===i("#wpadminbar").css("position")&&(n+=i("#wpadminbar").outerHeight()),i("header").length>0&&"fixed"===i("header").css("position")&&(n+=i("header").outerHeight());var s=i(window).scrollTop()+window.innerHeight,l=t.offset().top+t.outerHeight()-s,p=window.innerHeight-n;l>0&&t.outerHeight()<.9*p?i("html, body").animate({scrollTop:i(window).scrollTop()+l+5},400):s<a&&i("html, body").animate({scrollTop:o.offset().top-n},600)}}))}))}));