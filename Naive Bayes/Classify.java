import java.awt.FlowLayout;
import javax.swing.*;
import java.io.*;
import java.awt.*;
import java.awt.event.*;
import java.util.*;  
import java.io.FileWriter;

public class Classify implements ActionListener, KeyListener{

    static String filename;
    private java.util.List<String> lines,tagged_lines; 
    private JTextArea textArea;
    private JLabel statusText;
    private int index,maxIndex;
    private JButton left,right;
    final String categories[] = {"Personal","Finance","Promo","Updates","Others"};
    static double likeliness[];
    static ArrayList<String> words;
    static ArrayList<Values> iness;

    public static void main(String args[])
    {
        prepare();
        Classify classify = new Classify();
        
    }
    public Classify()
    {
        JFrame frame = new JFrame("Classify");
        JPanel panel1 = new JPanel();
        JPanel panel2 = new JPanel();
        panel1.setLayout(new FlowLayout());
 
        //Panel 1
        /*
        textArea = new JTextArea(2, 30);
        textArea.setText("Message goes here!");
        textArea.setWrapStyleWord(true);
        textArea.setLineWrap(true);
        textArea.setOpaque(false);
        textArea.setEditable(false);
        textArea.setFocusable(false);
        panel1.add(textArea);
        */
        
        //Panel 1
        final JTextField msgBox = new JTextField("Enter the SMS here...\n\n",30);
        //msgBox.setMaximumSize(new Dimension(1,5));
        panel1.add(msgBox);

        //Panel 2
        textArea = new JTextArea(50, 30);
        //textArea.setText("Message goes here!");
        textArea.setWrapStyleWord(true);
        textArea.setLineWrap(true);
        textArea.setOpaque(false);
        textArea.setEditable(false);
        textArea.setFocusable(false);
        panel2.add(textArea);

        msgBox.addActionListener(new ActionListener() {
                public void actionPerformed(ActionEvent e) {
                    if(msgBox.getText().equalsIgnoreCase("end"))
                        System.exit(0);
                    String result = predict_lns(msgBox.getText());
                    textArea.setText(result);
                    //statements!!!
            }});


        
        //Frame
        frame.setLayout(new GridLayout(2, 20));
        //frame.add(panel1,BorderLayout.NORTH);
        //frame.add(panel2,BorderLayout.CENTER);
        frame.add(panel1);
        frame.add(panel2);
        //frame.add(msgBox);
        //frame.add(textArea);
        
        frame.setSize(600, 300);
        frame.setLocationRelativeTo(null);
        frame.setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
        frame.setVisible(true);

    } 
    public void actionPerformed(ActionEvent e)
    { 
        String category = e.getActionCommand();
        
        //Check for button pressed to be <, >
        if(category.equals(">"))
          ;//  forward();
        else if(category.equals("<"))
            ;//rewind(); 

    } 



    //-------------------KeyListener Handler-----------------------
    /** Handle the key typed event from the text field. */
    public void keyTyped(KeyEvent e) {
        //displayInfo(e);
    }

    /** Handle the key-pressed event from the text field. */
    public void keyPressed(KeyEvent e) {
        //displayInfo(e);
    }

    /** Handle the key-released event from the text field. */
    public void keyReleased(KeyEvent e) {
        key_execute(e);
    }

    public void key_execute(KeyEvent e)
    {
        //You should only rely on the key char if the event
        //is a key typed event.
        int id = e.getID();
        if (id == KeyEvent.KEY_TYPED) 
        {
            char c = e.getKeyChar();
            System.out.println(c);
            //Do whatever
        } 
        else {
            int keyCode = e.getKeyCode();
            if(keyCode==KeyEvent.VK_LEFT)
                ;//rewind();
            else if(keyCode==KeyEvent.VK_RIGHT)
                ;//forward();
        }
    }





    public static String predict_lns(String sms)
    {
        final String delim_regex = "[^a-zA-Z]";

        sms=sms.toLowerCase();
        sms=sms.replaceAll("\\(s\\)", " ");
        sms=sms.replaceAll("\\[s\\]", " ");
        sms=sms.replaceAll(delim_regex, " ");

        String[] word_list = sms.split("\\s+");
        //Stemming
        String[] sms_words = Stemmer.stem_list(word_list);

        //sms_words = ArrayUtils.removeElement(sms_words, "");

        double scores[] = {1,1,1,1};
        boolean set[] = {false,false,false,false};


        for ( String word : sms_words) 
        {
            if(words.contains(word))
            {
                double val[] = iness.get(words.indexOf(word)).values;
                for(int i=0;i<4;i++)
                    if(val[i]!=0)
                    {
                        set[i]=true;
                        scores[i] = scores[i]*val[i];
                    }
            }

        }

        String result = "Likeliness scores: \n";

        String categories[]={"Personal","Finance","Promotional","Updates"};
        int max_score =0; /////i=1 and max_score = 1 -> personal filter
        for(int i=0;i<4;i++)
        {
            if(!set[i])
                scores[i]=0;
            
            result+="\n"+categories[i]+": "+scores[i];
            if(scores[i]>scores[max_score])
            {
                max_score=i;
            }
        }
        if(set[0]||set[1]||set[2]||set[3])
            result+="\n\nPredicted Category: "+categories[max_score];
        else
            result+="\n\nNo Category Predicted!!";
            //return -1;
        return result;
    }


    public static void predict_gui(String sms)throws IOException
    {
        final String delim_regex = "[^a-zA-Z]";

        sms=sms.toLowerCase();
        sms=sms.replaceAll("\\(s\\)", " ");
        sms=sms.replaceAll("\\[s\\]", " ");
        sms=sms.replaceAll(delim_regex, " ");

        String[] word_list = sms.split("\\s+");

        //Stemming
        String[] sms_words = Stemmer.stem_list(word_list);

        double scores[] = {1,1,1,1};
        boolean set[] = {false,false,false,false};


        for ( String word : sms_words) 
        {
            if(words.contains(word))
            {
                double val[] = iness.get(words.indexOf(word)).values;
                for(int i=0;i<4;i++)
                    if(val[i]!=0)
                    {
                        set[i]=true;
                        scores[i] = scores[i]*val[i];
                    }
                System.out.print(word+iness.get(words.indexOf(word)).print());
            }
            else
                System.out.print(word + " - no score registered!!");
            System.out.println();
        }

        System.out.println("------------------------------------------------");

        String categories[]={"Personal","Finance","Promotional","Updates"};
        int max_score =0;
        for(int i=0;i<4;i++)
        {
            if(!set[i])
                scores[i]=0;
            
            System.out.println(categories[i]+": "+scores[i]);
            if(scores[i]>scores[max_score])
            {
                max_score=i;
            }
        }

        if(set[0]||set[1]||set[2]||set[3])
            System.out.println("\nPredicted category : "+categories[max_score]+"\n");
        else
            System.out.println("\nNo prediciton :/");

        System.out.println("------------------------------------------------");


    }



    public static void prepare()
    {
        BufferedReader fr = null;

        words = new ArrayList<String>();
        iness = new ArrayList<Values>();

        try {

            fr = new BufferedReader(new FileReader("iness.csv"));
            
            //Extract probability of each class and store in likeliness.
            String line = fr.readLine();
            String[] lkns_list = line.split(",");
            double lkns_temp[] = new double[4];
            
            for (int i=0;i<4 ;i++) 
                lkns_temp[i]=Double.parseDouble(lkns_list[i]);
            likeliness = lkns_temp;

            while ((line = fr.readLine()) != null) 
            {
                // use comma as separator
                String[] list = line.split(",");
                words.add(list[0]);

                iness.add( new Values(list[1], list[2], list[3], list[4]));
            }

        } catch (FileNotFoundException e) {
            e.printStackTrace();
        } catch (IOException e) {
            e.printStackTrace();
        } finally {
            if (fr != null) {
                try {
                    fr.close();
                } catch (IOException e) {
                    e.printStackTrace();
                }
            }
        }
    }
}