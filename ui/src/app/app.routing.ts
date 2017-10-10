import { Routes, RouterModule } from '@angular/router';
import { UmlComponent} from './_component/uml/uml.component'
import { CodeNavgatorComponent} from './_component/code-navgator/code-navgator.component'

const appRoutes: Routes = [
  { path: 'uml', component: UmlComponent},
  { path: 'codeNavigator', component: CodeNavgatorComponent},
  { path: '**', redirectTo: 'uml' },
];

export const routing = RouterModule.forRoot(appRoutes);
