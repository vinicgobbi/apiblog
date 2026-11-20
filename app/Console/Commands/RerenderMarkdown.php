<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Models\Post;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

#[Signature('posts:rerender')]
#[Description('Renderiza novamente o HTML de todos os posts (inclusive na lixeira) e páginas')]
class RerenderMarkdown extends Command
{
    public function handle(): int
    {
        $posts = $this->rerender(Post::withTrashed()->lazyById());
        $pages = $this->rerender(Page::query()->lazyById());

        $this->info("{$posts} post(s) e {$pages} página(s) renderizados novamente.");

        return self::SUCCESS;
    }

    /**
     * Salva sem disparar eventos e sem alterar updated_at: o conteúdo
     * escrito pelo autor não mudou, apenas a sua renderização.
     *
     * @param  iterable<Post|Page>  $models
     */
    private function rerender(iterable $models): int
    {
        $count = 0;

        foreach ($models as $model) {
            /** @var Model&Post|Page $model */
            $model->renderMarkdown();
            $model->timestamps = false;
            $model->saveQuietly();
            $count++;
        }

        return $count;
    }
}
