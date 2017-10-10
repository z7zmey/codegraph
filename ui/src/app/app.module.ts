import { BrowserModule } from '@angular/platform-browser';
import { NgModule } from '@angular/core';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { HttpModule } from '@angular/http';
import { NgbModule } from '@ng-bootstrap/ng-bootstrap';
import { TreeModule } from 'angular-tree-component';

import { PrismComponent } from 'angular-prism';

import { ApiService } from './_service/api.service';

import { AppComponent } from './app.component';
import { NavComponent } from './_component/nav/nav.component';
import { CodeNavgatorComponent } from './_component/code-navgator/code-navgator.component';

import { routing } from './app.routing';

import { UmlComponent } from './_component/uml/uml.component';

@NgModule({
  declarations: [
    AppComponent,
    NavComponent,
    CodeNavgatorComponent,
    PrismComponent,
    UmlComponent
  ],
  imports: [
    routing,
    NgbModule.forRoot(),
    TreeModule,
    BrowserModule,
    FormsModule,
    ReactiveFormsModule,
    HttpModule,
  ],
  providers: [ApiService],
  bootstrap: [AppComponent],
})
export class AppModule {
}
