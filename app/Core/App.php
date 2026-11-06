<?php
declare(strict_types=1);
namespace App\Core;

final class App
{
    public static function run(): void
    {
        Env::load();
        $debug = !Env::isProduction() && Env::get('APP_DEBUG') === '1';
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('log_errors', '1');
        ini_set('error_log', storage_path('logs/php-error.log'));
        error_reporting(E_ALL);
        date_default_timezone_set((string)Env::get('APP_TIMEZONE', 'Asia/Aden'));
        mb_internal_encoding('UTF-8');

        $req = new Request();
        try {
            Session::start();
            Session::flashSweep();
            if (!is_file(base_path('storage/installed.lock')) && !str_starts_with($req->path(), '/install') && !str_starts_with($req->path(), '/assets')) {
                // تحديث/نقل مجلد: القاعدة مثبّتة و.env موجود لكن ملف القفل مفقود → يُستعاد تلقائيًا بدل إعادة التثبيت
                if (!\App\Controllers\InstallController::healLock()) { Response::redirect('/install')->send(); return; }
            }
            if (is_file(base_path('storage/installed.lock')) && !str_starts_with($req->path(), '/assets')) { \App\Core\Updater::maybe(); }
            if (\App\Services\Brand::enabled('maintenance') && !Auth::isPlatform() && !preg_match('#^/(login|logout|assets|brand|install|pay/callback)#', $req->path()) && $req->path() !== '/sw.js') {
                throw new HttpException(503, \App\Services\Brand::get('maintenance_message'));
            }
            $router = new Router();
            $router->use(\App\Middleware\SecurityHeaders::class);
            $router->use(\App\Middleware\CsrfMiddleware::class);
            require base_path('app/routes.php');
            $res = $router->dispatch($req);
        } catch (HttpException $e) {
            \App\Services\SecurityLog::fromException($e, $req);
            $res = self::errorResponse($req, $e->status, $e->getMessage(), $e->fields);
        } catch (\PDOException $e) {
            // أخطاء القاعدة المتوقعة تُترجم لرسائل مفهومة بدل «خطأ غير متوقع»
            [$st, $msg] = self::dbMessage($e);
            Logger::error($e->getMessage(), ['file' => $e->getFile() . ':' . $e->getLine(), 'uri' => $req->path()]);
            $res = self::errorResponse($req, $st, $debug && $st === 500 ? $e->getMessage() : $msg);
        } catch (\Throwable $e) {
            Logger::error($e->getMessage(), ['file' => $e->getFile() . ':' . $e->getLine(), 'uri' => $req->path()]);
            $res = self::errorResponse($req, 500, $debug ? $e->getMessage() : 'حدث خطأ غير متوقع. حاول لاحقًا.');
        }
        $res->send();
    }

    /** @return array{0:int,1:string} */
    public static function dbMessage(\PDOException $e): array
    {
        return match ((int)($e->errorInfo[1] ?? 0)) {
            1062 => [409, 'هذه القيمة مستخدمة مسبقًا (مكررة). غيّرها أو ابحث عن السجل الموجود.'],
            1451 => [409, 'لا يمكن الحذف لأن هناك بيانات أخرى مرتبطة به (مثل طلاب أو درجات). احذف المرتبط أولًا أو أبقِه.'],
            1452 => [422, 'أحد الاختيارات غير موجود أو حُذف. أعد تحميل الصفحة واختر من القائمة.'],
            1406 => [422, 'أحد النصوص أطول من المسموح. اختصره ثم احفظ.'],
            1264, 1366 => [422, 'قيمة رقمية أو تاريخ غير صالح في أحد الحقول.'],
            1048 => [422, 'حقل مطلوب ناقص. أكمل الحقول المطلوبة ثم احفظ.'],
            2002, 2006, 1045, 1049 => [503, 'تعذّر الاتصال بقاعدة البيانات. تأكد أن خادم MySQL يعمل وأن إعدادات .env صحيحة.'],
            default => [500, 'حدث خطأ في حفظ البيانات. حاول لاحقًا، وإن تكرر فأبلغ الدعم الفني.'],
        };
    }
    public static function errorResponse(Request $req, int $status, string $msg, array $fields = []): Response
    {
        if ($req->isAjax()) { return Response::fail('http_' . $status, $msg ?: 'خطأ', $status, $fields); }
        $view = in_array($status, [403, 404, 405, 419, 503], true) ? "errors/$status" : 'errors/500';
        if (!is_file(base_path("app/Views/$view.php"))) { $view = 'errors/500'; }
        return Response::html(View::render($view, ['message' => $msg, 'status' => $status], 'bare'), $status);
    }
}
