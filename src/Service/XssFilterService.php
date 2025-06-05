<?php

namespace Elenyum\Maker\Service;

use voku\helper\AntiXSS;

/**
 * Фильтр XSS для обработки содержимого запроса
 */
class XssFilterService
{
    private AntiXSS $antiXss;

    public function __construct()
    {
        $this->antiXss = new AntiXSS();
        // Настройка AntiXSS для удаления опасных тегов и атрибутов
        $this->antiXss->removeEvilAttributes(['style', 'on.*']);
        $this->antiXss->removeEvilHtmlTags(['script', 'iframe', 'object', 'embed']);
    }

    /**
     * Фильтрует содержимое запроса от XSS-атак, возвращает строку
     *
     * @param string $content
     * @return string Отфильтрованная строка (JSON или обычная)
     * @throws \JsonException
     */
    public function filterRequestContent(string $content): string
    {
        // Проверяем, пустой ли контент
        if (empty($content)) {
            return $content;
        }

        // Проверяем, является ли строка корректным JSON
        $data = @json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            // Если JSON корректен, очищаем данные рекурсивно
            $sanitizedData = $this->sanitizeData($data);
            // Кодируем обратно в JSON
            return json_encode($sanitizedData, JSON_THROW_ON_ERROR);
        }

        // Если не JSON, очищаем как строку
        return $this->antiXss->xss_clean($content);
    }

    /**
     * Рекурсивно очищает данные от XSS
     *
     * @param mixed $data
     * @return mixed
     */
    private function sanitizeData(mixed $data): mixed
    {
        if (is_string($data)) {
            return $this->antiXss->xss_clean($data);
        }

        if (is_array($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                // Очищаем ключ и значение
                $sanitizedKey = is_string($key) ? $this->antiXss->xss_clean($key) : $key;
                $result[$sanitizedKey] = $this->sanitizeData($value);
            }
            return $result;
        }

        // Возвращаем неизменённые данные для других типов (числа, булевы и т.д.)
        return $data;
    }
}