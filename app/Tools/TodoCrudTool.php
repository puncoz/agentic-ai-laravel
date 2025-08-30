<?php

namespace App\Tools;

use App\Models\Todo;

class TodoCrudTool
{
    public string $info = <<<__INFO__
- function list(): array of {id: number, todo: string}
  Description: A function that returns all the todos
- function create(todo: string): integer
  Description: A function that accepts a todo string and returns the id of the created todo
- function search(searchTerm: string): array of {id: number, todo: string}
  Description: A function that accepts a search term string and returns matching todos
- function delete(id: number): void
  Description: A function that accepts a todo id and deletes the corresponding todo
__INFO__;

    public string $schema = <<<__SCHEMA__
- id: number (unique identifier for each todo)
- todo: string (the task description)
- created_at: timestamp (the time when the todo was created)
- updated_at: timestamp (the time when the todo was last updated)
__SCHEMA__;


    public function list(): array
    {
        return Todo::all()->toArray();
    }

    public function create(string $todo): int
    {
        /** @var Todo $todoModel */
        $todoModel = Todo::create(['todo' => $todo]);

        return $todoModel->id;
    }

    public function search(string $searchText): array
    {
        return Todo::query()->where('todo', 'ilike', '%'.$searchText.'%')->get()->toArray();
    }

    public function delete(int $id): void
    {
        Todo::destroy($id);
    }
}
