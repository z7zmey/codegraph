import { Component } from '@angular/core';
import { ApiService } from './_service/api.service';
import { Router } from '@angular/router';
import { Response } from '@angular/http';

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.css'],
})
export class AppComponent {
  nodes = []

  constructor(private apiService: ApiService, private router: Router) {
    this.apiService.getPathTree()
      .subscribe(
        (response: Response) => {
          if (response.status !== 200) {
            alert('Error! See log for details.');
            console.log('response', response);
          }

          this.nodes = [response.json()]
        },
      );
  }

  protected onEvent($event) {
    this.router.navigate(['/uml',  { path: $event.node.id }]);
    console.log($event);
  }
}
