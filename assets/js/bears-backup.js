/**
 * @package Bears Demo Content
 * @version 1.0.0
 * @author Bearsthemes
 */

import DemoContent from "./modules/demo-content";
import Backup from "./modules/backup";
// import '../css/main.scss';

!(function(w, $) {
  "use strict";
  ELEMENT.locale(ELEMENT.lang.en);

  // Helpers
  w.bbackup_helpers = require("./modules/helpers");

  // DOM Ready
  $(function() {
    new Vue(Backup());
  });

  // Browser load complete
  $(w).load(function() {}); 
})(window, jQuery);
