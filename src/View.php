<?php

declare(strict_types=1);

final class View
{
    public function __construct(
        private readonly string $basePath
    ) {}

    /**
     * @param array<string,mixed> $params
     */
    public function render(string $template, array $params = []): void
    {
        $templateFile = rtrim($this->basePath, '/') . '/' . $template . '.php';
        if (!is_file($templateFile)) {
            http_response_code(500);
            echo "Template not found: " . e($template);
            return;
        }

        extract($params, EXTR_SKIP);

        ob_start();
        require $templateFile;
        $content = ob_get_clean();

        require rtrim($this->basePath, '/') . '/layout.php';
    }
}
