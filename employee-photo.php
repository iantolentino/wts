<?php
// Employee photos are retired. Existing stored data is preserved but never served.
http_response_code(404);
exit('Not found.');
