# CourseEditor
### Introduction
First of all, what's **Course Editor**?
It is a MediaWiki extension which helps users to create structured courses in an interactive and easy way.

The units of a course are chapters and sections, so the structure is the following:
a **course** is composed by one or more **chapters** that are composed by one or more **sections**.

                 Course
                    |
                   / \
                  /   \
           Chapter1   Chapter2
              |          |
             / \      Section1
            /   \
      Section1  Section2

Moreover the course could be **public** or **private**. A private course is created _"behind"_ the user page, the public one in the WikiToLearn department or topic from which the user started the creation process. A private course can be _"flagged"_ as ready to be published, so admins will approve the request and the course will be public.
     
### Essential units
Now we're going to delve into specific details about each essential unit. This bulleted list describes the attributes associated with each of the parties already reflected.

- _Course_:
  - Title (name)
  - Topic
  - Description
  - Bibliography
  - Exercises
  - Books
  - External references
  - Imported (yes/no)
  - Reviewed (yes/no)
- _Chapter_:
  - Title (name)
- _Section_:
  - Title (name)
  
As you can see, the course has lots of attributes. They are grouped into page we called metadata page. We will address the implementation details in a moment.
The most important thing for now is to have understood the structure and attributes that can be defined for a course.

### Extension design
This section is more developers oriented. It describes the technology design and the choices taken. Obviously they're not the _best_ solutions, but they were valuated using pros and cons. Design a system is always a trade-off.

#### Storage the course structure
Where is the best place to store the structure of the courses? A database table may be the most obvious answer, but it isn't the our.

Use the MediaWiki database couples the extension to the upstream changes. We spent more than one week to fix an extension that was broken after a new upstream release. The issue was related to the changes upstream made on the database schema.
Moreover we faced another problem: how we can display the table of contents of a course? I mean, show the structure to the user within a wiki page. Not really simple using references stored within a db table. This is why we decided that the structure is stored within wiki pages. So they can be easily **transcluded** in other pages, we can count on Templates and so on.

#### Metadata
Cause of the storage decision, also metadata are stored within wiki pages. Are you get scared about this? We too. In the next section (Implementation details) you will find more details on how this is impletemented. Metadata pages are associated to a new namespace we called _**`CourseMetadata:`**_. Moreover there are some metadata reserved to admins: _Imported_ and _Reviewed_.

#### Public and private
We already said that either public and private courses exist. The public ones are associated to a new namespace called _**`Course:`**_, private ones are created as subpages of the user page, for instance _**`User:Foo/Bar_course`**_.

The owner of a private course is free to delete and move pages. This is not true for public ones. Only admins are allowed to delete page directly, otherwise the page or the pages, are only removed from the related structure and a specific template and category is added.

#### Publish courses
When a user is finally sure about a course, he/she can publish it. Templates and categories helped us to flag _ready courses_. A subpage of the main extension Special Page is reserved to admins and allow them approve publish requests. Then they are invited to link the course in the proper topic and department pages.

#### Complex operations
The most difficult design choice was related to complex operations such us rename a chapter. Let's see the steps we have to perform:

1. Rename the chapter page
2. Rename all the sections of the chapters (cause they are subpages)
3. Update the sections links within the chapter page
4. Update the transclusion of the chapter page within the course page

All of them are internal API calls that are not queued using a FIFO logic. Is quite impossible to execute them step by step and the job queue API is a mess (upstream developers agree with it).

So we decided to split the business logic and put the _orchestration_ in the client side. The big operations are splitted into micro-operations to avoid timeouts and are executed using jQuery queue functions.

#### UI
The user interface is realized exploiting the Special pages and the url parameters. Mainly there are 4 views:
- Course creation (to create a public or a private course)
- Course/chapters editor (to manage course/chapters structure, crud operations)
- Metadata editor (to manage metadata of the course)
- Publish course (to approve publish requests - _admins only_)

### Implementation details
The extension is composed by:
- `CourseEditor.php`: the extenstion loader, nothing special, like boilerplate
- `CourseEditor.namespaces.php`: define the new namespaces and the aliases for other languages
- `CourseEditor.alias.php`: aliases for the name of the extions
- `CourseEditorOperations.php`: where the logic of the operation lies
- `CourseEditorTemplates.php`: HTML + PHP templates for the views
- `CourseEditorUtils.php`: common utils functions like wrappers etc...
- `SpecialCourseEditor.php`: the application entrypoint, like a façade where urls and params are defined and managed
- `modules/commonFunctions.js`: common functions for the JS modules
- `modules/courseEditor.js`: JS module for the course editor view
- `modules/levelTwoEditor.js`: JS module for the level two editor (generalized name of chapter) view
- `modules/createCourse.js`: JS module for the create view
- `modules/manageMetadata.js`: JS module for the manage metadata view
- `modules/publishCourse.js`: JS module for the admin publish courses view
