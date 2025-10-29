<?php

// محاكاة بيئة ووردبريس

// محاكاة كلاس WP_Error إذا لم يكن موجودًا
if (!class_exists('WP_Error')) {
    class WP_Error {}
}

define('ABSPATH', dirname(__DIR__) . '/');
const siteurl = 'http://example.com'; // تعريف الثابت المستخدم في الملف

// محاكاة دوال ووردبريس
if (!function_exists('search_parameters_filter')) {
    function search_parameters_filter($params) { return $params; }
}
if (!function_exists('is_rtl')) {
    function is_rtl() { return false; }
}
if (!function_exists('get_term')) {
    // هذه هي المحاكاة الأساسية. ستعيد قيمة فارغة للمعرف غير الصالح
    function get_term($term_id, $taxonomy = '', $output = 'OBJECT', $filter = 'raw') {
        if ($term_id == 9999) { // المعرف غير الصالح الذي سنستخدمه في الاختبار
            return null;
        }
        // يمكن إرجاع كائن WP_Term وهمي للمعرّفات الصالحة إذا لزم الأمر
        $term = new stdClass();
        $term->name = 'Valid Term';
        return $term;
    }
}
if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {
        return $thing instanceof WP_Error;
    }
}
if (!function_exists('esc_html')) {
    function esc_html($text) { return $text; }
}
if (!function_exists('get_search_box')) {
    function get_search_box() { echo "<!-- search box mock -->"; }
}
if (!function_exists('search_helper')) {
    function search_helper($params) { return []; }
}
// محاكاة WP_Query
if (!class_exists('WP_Query')) {
    class WP_Query {
        public $posts = [];
        public function __construct($args) {}
        public function have_posts() { return false; }
        public function the_post() {}
    }
}
if (!function_exists('get_my_project_box')) {
    function get_my_project_box($id) {}
}
if (!function_exists('get_my_property_box')) {
    function get_my_property_box($id) {}
}
if (!function_exists('wp_reset_postdata')) {
    function wp_reset_postdata() {}
}

// تضمين ملف القالب المراد اختباره
require_once ABSPATH . 'app/templates/pages/search.php';

// --- حالة الاختبار ---

echo "بدء الاختبار...\n";

// 1. إعداد معلمات الاختبار بمعرف تصنيف غير صالح
$test_parameters = [
    'st'   => 1,
    'city' => 9999, // معرف مدينة غير صالح
    'type' => 9999, // معرف نوع غير صالح
    's'    => 'test'
];

// 2. التقاط المخرجات واستدعاء الدالة
ob_start();
page_advanced_search_body($test_parameters);
$output = ob_get_clean();

// 3. التأكد من النتائج
// قبل الإصلاح، كان هذا يسبب خطأ فادحًا ويتوقف السكريبت.
// إذا وصل السكريبت إلى هذه النقطة واحتوت المخرجات على العلامات المتوقعة، ينجح الاختبار.
if (strpos($output, '<div class="units-page">') !== false) {
    echo "نجح الاختبار: تم تنفيذ الدالة دون خطأ فادح عند إعطاء معرفات تصنيف غير صالحة.\n";
} else {
    echo "فشل الاختبار: لم تعرض الدالة المخرجات المتوقعة. ربما توقفت عن العمل.\n";
    echo "المخرجات كانت:\n" . $output;
}