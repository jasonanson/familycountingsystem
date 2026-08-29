<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AttachmentController extends Controller
{
    /**
     * 上傳發票/單據照片或 PDF 附件
     */
    public function store(Request $request, Transaction $transaction)
    {
        if (! Auth::check()) {
            $defaultUser = \App\Models\User::where('account', 'parent')->first() ?? \App\Models\User::first();
            if ($defaultUser) Auth::login($defaultUser);
        }

        $request->validate([
            'attachment' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
        ], [
            'attachment.required' => '請選擇要上傳的單據/發票檔案。',
            'attachment.file' => '上傳的檔案無效。',
            'attachment.mimes' => '附件格式僅支援 JPG, PNG, WEBP 與 PDF 檔案。',
            'attachment.max' => '附件檔案大小不能超過 5MB。',
        ]);

        $file = $request->file('attachment');
        $directory = public_path('attachments');
        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true, true);
        }

        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension() ?: 'bin';
        $filename = 'att_' . time() . '_' . Str::random(8) . '.' . $extension;

        // 移至 public/attachments 目錄
        $file->move($directory, $filename);

        $filePath = '/attachments/' . $filename;
        $fileSize = File::size($directory . '/' . $filename);
        $mimeType = $file->getClientMimeType();

        // 建立 Attachment 資料紀錄
        $attachment = Attachment::create([
            'transaction_id' => $transaction->id,
            'family_id' => $transaction->family_id ?? Auth::user()?->current_family_id,
            'user_id' => Auth::id(),
            'file_path' => $filePath,
            'file_name' => $originalName,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
        ]);

        // 同步更新 Transaction 的 attachment_ids 與 custom_fields 相容欄位
        $attachmentIds = $transaction->attachment_ids ?? [];
        if (! in_array($attachment->id, $attachmentIds)) {
            $attachmentIds[] = $attachment->id;
            $transaction->attachment_ids = array_values($attachmentIds);
        }

        $customFields = $transaction->custom_fields ?? [];
        $customFields['attachment'] = $filePath;
        $transaction->custom_fields = $customFields;
        $transaction->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => '🎉 附件上傳成功！',
                'attachment' => $attachment,
            ]);
        }

        return back()->with('success', '🎉 單據發票附件已成功上傳儲存！');
    }

    /**
     * 刪除指定附件紀錄與實體檔案
     */
    public function destroy(Attachment $attachment)
    {
        if (! Auth::check()) {
            $defaultUser = \App\Models\User::where('account', 'parent')->first() ?? \App\Models\User::first();
            if ($defaultUser) Auth::login($defaultUser);
        }

        $transaction = $attachment->transaction;

        // 刪除 public 目錄下的實體檔案
        $physicalPath = public_path(ltrim($attachment->file_path, '/'));
        if (File::exists($physicalPath)) {
            File::delete($physicalPath);
        }

        // 從交易紀錄的 attachment_ids 與 custom_fields 中移除關聯
        if ($transaction) {
            $attachmentIds = array_diff($transaction->attachment_ids ?? [], [$attachment->id]);
            $transaction->attachment_ids = array_values($attachmentIds);

            $customFields = $transaction->custom_fields ?? [];
            if (isset($customFields['attachment']) && $customFields['attachment'] === $attachment->file_path) {
                unset($customFields['attachment']);
            }
            $transaction->custom_fields = $customFields;
            $transaction->save();
        }

        $attachment->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => '附件已成功刪除。',
            ]);
        }

        return back()->with('success', '單據附件檔案與紀錄已成功刪除。');
    }
}
