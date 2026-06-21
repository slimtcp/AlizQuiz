(function () {
    var MAX_TILT   = 8;
    var MAX_GLARE  = 0.18;
    var SPRING     = 'cubic-bezier(.03,.98,.52,.99)';
    var TRANSITION = '0.15s ' + SPRING;
    var RESET_DUR  = '0.5s ease';

    function initTilt(card) {
        card.style.willChange     = 'transform';
        card.style.transformStyle = 'preserve-3d';

        var glare = document.createElement('div');
        glare.style.cssText = [
            'position:absolute', 'inset:0', 'border-radius:inherit',
            'pointer-events:none', 'opacity:0',
            'background:radial-gradient(circle at 50% 50%, rgba(255,255,255,' + MAX_GLARE + ') 0%, transparent 70%)',
            'transition:opacity ' + TRANSITION,
            'z-index:1'
        ].join(';');
        card.appendChild(glare);

        function onMove(e) {
            var rect  = card.getBoundingClientRect();
            var cx    = e.clientX - rect.left;
            var cy    = e.clientY - rect.top;
            var px    = (cx / rect.width  - 0.5) * 2;
            var py    = (cy / rect.height - 0.5) * 2;

            var rotX  = -py * MAX_TILT;
            var rotY  =  px * MAX_TILT;

            card.style.transition = 'transform ' + TRANSITION + ', box-shadow ' + TRANSITION;
            card.style.transform  =
                'perspective(900px) rotateX(' + rotX.toFixed(2) + 'deg) rotateY(' + rotY.toFixed(2) + 'deg) scale3d(1.02,1.02,1.02)';
            card.style.boxShadow  =
                (rotY * 1.5) + 'px ' + (-rotX * 1.5) + 'px 40px rgba(61,124,255,0.25), ' +
                '0 0 0 1px rgba(61,124,255,0.35)';

            glare.style.opacity = '1';
            glare.style.backgroundImage =
                'radial-gradient(circle at ' + (cx / rect.width * 100).toFixed(1) + '% ' +
                (cy / rect.height * 100).toFixed(1) + '%, rgba(255,255,255,' + MAX_GLARE + ') 0%, transparent 65%)';
        }

        function onLeave() {
            card.style.transition = 'transform ' + RESET_DUR + ', box-shadow ' + RESET_DUR;
            card.style.transform  = 'perspective(900px) rotateX(0deg) rotateY(0deg) scale3d(1,1,1)';
            card.style.boxShadow  = '';
            glare.style.opacity   = '0';
        }

        card.addEventListener('mousemove', onMove);
        card.addEventListener('mouseleave', onLeave);
    }

    function apply() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        if (window.matchMedia('(pointer: coarse)').matches) return;
        document.querySelectorAll('.question-card').forEach(initTilt);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', apply);
    } else {
        apply();
    }
})();
